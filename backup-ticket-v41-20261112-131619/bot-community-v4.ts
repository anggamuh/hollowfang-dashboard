import { createServer, type Server } from 'node:http';
import { timingSafeEqual, randomUUID } from 'node:crypto';
import { mkdir, readFile, rename, writeFile } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import {
  ActionRowBuilder, ButtonBuilder, ButtonStyle, ChannelType, Events,
  GatewayIntentBits, PermissionFlagsBits, type Client, type GuildMember,
  type Message, type Interaction, type PartialGuildMember,
} from 'discord.js';

// Community extension for the EXISTING ManukAPP client. Off unless configured.
type Config = {
  welcomeEnabled: boolean; welcomeChannelId: string; welcomeText: string;
  goodbyeEnabled: boolean; goodbyeChannelId: string; goodbyeText: string;
  autoModEnabled: boolean; spamLimit: number; spamWindowSeconds: number;
  blockedWords: string[]; blockLinks: boolean; exemptRoleIds: string[];
  ticketCategoryId: string; ticketSupportRoleId: string;
  selfRoles: string[];
};
type Ticket = { id: string; channelId: string; userId: string; createdAt: string; status: 'open'|'closed' };
type Activity = { at: string; action: string; actor: string; detail: string };
type State = { config: Config; tickets: Ticket[]; activity: Activity[] };
const defaults: Config = {
  welcomeEnabled: false, welcomeChannelId: '', welcomeText: 'Selamat datang {user} di {server}!',
  goodbyeEnabled: false, goodbyeChannelId: '', goodbyeText: '{username} telah meninggalkan {server}.',
  autoModEnabled: false, spamLimit: 6, spamWindowSeconds: 10,
  blockedWords: [], blockLinks: false, exemptRoleIds: [],
  ticketCategoryId: '', ticketSupportRoleId: '', selfRoles: [],
};
const idValid = (x: unknown): x is string => typeof x === 'string' && /^\d{15,22}$/.test(x);
const restrictedSelfRole = (role: { permissions: { any: (flags: bigint[], checkAdmin?: boolean) => boolean } }) => role.permissions.any([
  PermissionFlagsBits.Administrator, PermissionFlagsBits.ManageGuild, PermissionFlagsBits.ManageRoles,
  PermissionFlagsBits.BanMembers, PermissionFlagsBits.KickMembers, PermissionFlagsBits.ModerateMembers,
  PermissionFlagsBits.ManageChannels, PermissionFlagsBits.ManageWebhooks, PermissionFlagsBits.ManageMessages,
  PermissionFlagsBits.MentionEveryone,
], false);
const clean = (x: unknown, limit = 500) => typeof x === 'string' ? x.trim().slice(0, limit) : '';
const disk = resolve(process.env.MANUK_COMMUNITY_DATA_FILE || './data/hollowfang-community.json');
function safeToken(header: string | undefined, token: string) {
  if (!header?.startsWith('Bearer ')) return false;
  const a = Buffer.from(header.slice(7)); const b = Buffer.from(token);
  return a.length === b.length && timingSafeEqual(a, b);
}
function row(customId: string, label: string, style: ButtonStyle = ButtonStyle.Primary) {
  return new ActionRowBuilder<ButtonBuilder>().addComponents(
    new ButtonBuilder().setCustomId(customId).setLabel(label).setStyle(style)
  );
}

export async function startCommunityV4(client: Client): Promise<Server> {
  const token = process.env.MANUK_API_TOKEN ?? '';
  const guildId = process.env.MANUK_MONITOR_GUILD_ID ?? '';
  const port = Number(process.env.MANUK_COMMUNITY_API_PORT || 3213);
  if (token.length < 32 || !idValid(guildId) || !Number.isInteger(port) || port < 1 || port > 65535)
    throw new Error('V4 requires MANUK_API_TOKEN, MANUK_MONITOR_GUILD_ID, valid port');
  let state: State = { config: { ...defaults }, tickets: [], activity: [] };
  try {
    const data: unknown = JSON.parse(await readFile(disk, 'utf8'));
    if (data && typeof data === 'object' && !Array.isArray(data)) {
      const s = data as Partial<State>;
      state = {
        config: { ...defaults, ...(s.config || {}) },
        tickets: Array.isArray(s.tickets) ? s.tickets.slice(-500) : [],
        activity: Array.isArray(s.activity) ? s.activity.slice(0, 300) : [],
      };
    }
  } catch (e) { if ((e as NodeJS.ErrnoException).code !== 'ENOENT') throw e; }
  let writeQueue: Promise<void> = Promise.resolve();
  function save() {
    const snapshot = JSON.stringify(state, null, 2);
    writeQueue = writeQueue.catch(() => {}).then(async () => {
      await mkdir(dirname(disk), { recursive: true });
      const tmp = `${disk}.${randomUUID()}.tmp`;
      await writeFile(tmp, snapshot, 'utf8');
      await rename(tmp, disk);
    });
    return writeQueue;
  }
  function record(action: string, actor: string, detail = '') {
    state.activity.unshift({ at: new Date().toISOString(), action, actor, detail: detail.slice(0, 200) });
    state.activity = state.activity.slice(0, 300);
  }
  const guild = await client.guilds.fetch(guildId);
  const textChannel = async (id: string) => {
    if (!idValid(id)) return null;
    const channel = await guild.channels.fetch(id).catch(() => null);
    if (!channel || (channel.type !== ChannelType.GuildText && channel.type !== ChannelType.GuildAnnouncement)) return null;
    const me = await guild.members.fetchMe();
    if (!channel.permissionsFor(me)?.has([PermissionFlagsBits.ViewChannel, PermissionFlagsBits.SendMessages])) return null;
    return channel;
  };
  const windows = new Map<string, number[]>();
  const onJoin = async (member: GuildMember) => {
    if (member.guild.id !== guildId || !state.config.welcomeEnabled) return;
    const channel = await textChannel(state.config.welcomeChannelId);
    if (!channel) return;
    await channel.send({ content: state.config.welcomeText.replaceAll('{user}', `<@${member.id}>`).replaceAll('{username}', member.user.username).replaceAll('{server}', member.guild.name), allowedMentions: { parse: [] } }).catch(() => {});
  };
  const onLeave = async (member: GuildMember | PartialGuildMember) => {
    if (member.guild.id !== guildId || !state.config.goodbyeEnabled) return;
    const channel = await textChannel(state.config.goodbyeChannelId);
    if (!channel) return;
    await channel.send({ content: state.config.goodbyeText.replaceAll('{username}', member.user.username).replaceAll('{server}', member.guild.name), allowedMentions: { parse: [] } }).catch(() => {});
  };
  const onMessage = async (msg: Message) => {
    if (!msg.inGuild() || msg.guildId !== guildId || msg.author.bot || !state.config.autoModEnabled) return;
    const member = msg.member;
    if (!member || member.permissions.has(PermissionFlagsBits.ManageMessages) ||
      member.roles.cache.some(r => state.config.exemptRoleIds.includes(r.id))) return;
    const content = msg.content.toLocaleLowerCase();
    const blocked = state.config.blockedWords.some(word => word && new RegExp(`(^|[^\\p{L}\\p{N}])${word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}($|[^\\p{L}\\p{N}])`, 'iu').test(content));
    const links = state.config.blockLinks && /https?:\/\/|discord\.gg\//i.test(content);
    const key = `${msg.guildId}:${msg.author.id}`;
    const now = Date.now();
    const timestamps = (windows.get(key) || []).filter(t => now - t < state.config.spamWindowSeconds * 1000);
    timestamps.push(now); windows.set(key, timestamps);
    const spam = timestamps.length >= state.config.spamLimit;
    if (!(blocked || links || spam)) return;
    if (!msg.deletable) return;
    try {
      await msg.delete();
      record('automod.delete', msg.author.id, blocked ? 'blocked word' : links ? 'link' : 'spam');
      await save();
    } catch { /* lacking permission; do not fake success */ }
  };
  const onInteraction = async (i: Interaction) => {
    if (!i.isButton() || !i.inGuild() || i.guildId !== guildId || !i.customId.startsWith('hf4:')) return;
    try {
      if (i.customId === 'hf4:ticket:open') {
        await i.deferReply({ ephemeral: true });
        const existing = state.tickets.find(t => t.status === 'open' && t.userId === i.user.id);
        if (existing) { await i.editReply('Kamu sudah punya tiket terbuka.'); return; }
        const me = await guild.members.fetchMe();
        if (!me.permissions.has(PermissionFlagsBits.ManageChannels)) { await i.editReply('Bot belum punya izin Manage Channels.'); return; }
        const opts = [
          { id: guild.roles.everyone.id, deny: [PermissionFlagsBits.ViewChannel] },
          { id: i.user.id, allow: [PermissionFlagsBits.ViewChannel, PermissionFlagsBits.SendMessages, PermissionFlagsBits.ReadMessageHistory] },
          { id: me.id, allow: [PermissionFlagsBits.ViewChannel, PermissionFlagsBits.SendMessages, PermissionFlagsBits.ManageChannels] },
        ];
        if (idValid(state.config.ticketSupportRoleId)) opts.push({ id: state.config.ticketSupportRoleId, allow: [PermissionFlagsBits.ViewChannel, PermissionFlagsBits.SendMessages, PermissionFlagsBits.ReadMessageHistory] });
        const category = idValid(state.config.ticketCategoryId) ? await guild.channels.fetch(state.config.ticketCategoryId).catch(() => null) : null;
        const channel = await guild.channels.create({ name: `ticket-${i.user.username.toLowerCase().replace(/[^a-z0-9-]/g, '').slice(0, 12) || 'member'}`, type: ChannelType.GuildText, parent: category?.type === ChannelType.GuildCategory ? category.id : undefined, permissionOverwrites: opts });
        const ticket: Ticket = { id: randomUUID(), channelId: channel.id, userId: i.user.id, createdAt: new Date().toISOString(), status: 'open' };
        state.tickets.push(ticket); record('ticket.open', i.user.id, channel.id); await save();
        await channel.send({ content: `Tiket dari <@${i.user.id}>. Jelaskan kebutuhanmu di sini.`, components: [row('hf4:ticket:close', 'Tutup tiket', ButtonStyle.Danger)], allowedMentions: { parse: [] } });
        await i.editReply(`Tiket berhasil dibuat: <#${channel.id}>`);
        return;
      }
      if (i.customId === 'hf4:ticket:close') {
        await i.deferReply({ ephemeral: true });
        const ticket = state.tickets.find(t => t.status === 'open' && t.channelId === i.channelId);
        if (!ticket) { await i.editReply('Tiket tidak ditemukan.'); return; }
        const m = await guild.members.fetch(i.user.id);
        if (ticket.userId !== i.user.id && !m.permissions.has(PermissionFlagsBits.ManageChannels) && !m.roles.cache.has(state.config.ticketSupportRoleId)) { await i.editReply('Tidak diizinkan menutup tiket.'); return; }
        const ticketChannel = await guild.channels.fetch(ticket.channelId).catch(() => null);
        if (!ticketChannel) { await i.editReply('Channel tiket sudah tidak tersedia.'); return; }
        await ticketChannel.delete('Hollowfang ticket closed');
        ticket.status = 'closed'; record('ticket.close', i.user.id, ticket.channelId); await save();
        return;
      }
      if (i.customId.startsWith('hf4:role:')) {
        await i.deferReply({ ephemeral: true });
        const roleId = i.customId.slice('hf4:role:'.length);
        if (!idValid(roleId) || !state.config.selfRoles.includes(roleId)) { await i.editReply('Role ini tidak lagi tersedia.'); return; }
        const member = await guild.members.fetch(i.user.id);
        const role = await guild.roles.fetch(roleId);
        if (!role || role.managed || !role.editable || restrictedSelfRole(role)) { await i.editReply('Role tidak boleh dibagikan lewat self-role.'); return; }
        const remove = member.roles.cache.has(roleId);
        if (remove) await member.roles.remove(role); else await member.roles.add(role);
        record(remove ? 'selfrole.remove' : 'selfrole.add', i.user.id, roleId); await save();
        await i.editReply(remove ? `Role ${role.name} dilepas.` : `Role ${role.name} diberikan.`);
      }
    } catch (e) {
      if (i.deferred || i.replied) await i.editReply('Operasi gagal. Cek permission bot dan hierarki role.').catch(() => {});
      else await i.reply({ content: 'Operasi gagal.', ephemeral: true }).catch(() => {});
    }
  };
  client.on(Events.GuildMemberAdd, onJoin);
  client.on(Events.GuildMemberRemove, onLeave);
  client.on(Events.MessageCreate, onMessage);
  client.on(Events.InteractionCreate, onInteraction);
  const server = createServer(async (req, res) => {
    res.setHeader('Content-Type', 'application/json'); res.setHeader('Cache-Control', 'no-store');
    const reply = (code: number, value: object) => { res.writeHead(code); res.end(JSON.stringify(value)); };
    if (!safeToken(req.headers.authorization, token)) return reply(401, { error: 'Unauthorized' });
    if (!client.isReady()) return reply(503, { error: 'Bot offline' });
    const url = new URL(req.url || '/', 'http://127.0.0.1');
    if (!url.pathname.startsWith('/api/v4/')) return reply(404, { error: 'Not found' });
    if (req.method === 'GET' && url.pathname === '/api/v4/status') return reply(200, {
      config: state.config, activity: state.activity.slice(0, 50), tickets: state.tickets.slice(-50).reverse(),
      capabilities: { welcome: client.options.intents.has(GatewayIntentBits.GuildMembers),
        automod: client.options.intents.has(GatewayIntentBits.GuildMessages) && client.options.intents.has(GatewayIntentBits.MessageContent) },
    });
    if (req.method !== 'POST') return reply(404, { error: 'Not found' });
    let raw = ''; for await (const chunk of req) { raw += chunk.toString(); if (Buffer.byteLength(raw) > 16_384) return reply(413, { error: 'Too large' }); }
    let body: Record<string, unknown>;
    try { const x: unknown = JSON.parse(raw); if (!x || typeof x !== 'object' || Array.isArray(x)) throw Error(); body = x as Record<string, unknown>; }
    catch { return reply(422, { error: 'Invalid JSON' }); }
    const actor = body.actor;
    if (!idValid(actor)) return reply(422, { error: 'Actor required' });
    const admin = await guild.members.fetch(actor).catch(() => null);
    if (!admin || (actor !== guild.ownerId && !admin.permissions.has(PermissionFlagsBits.Administrator))) return reply(403, { error: 'Guild admin required' });
    try {
      if (url.pathname === '/api/v4/settings') {
        const c = body.config;
        if (!c || typeof c !== 'object' || Array.isArray(c)) return reply(422, { error: 'Config required' });
        const x = c as Record<string, unknown>;
        const welcomeEnabled = x.welcomeEnabled === true, goodbyeEnabled = x.goodbyeEnabled === true, autoModEnabled = x.autoModEnabled === true;
        const welcomeChannelId = clean(x.welcomeChannelId, 25), goodbyeChannelId = clean(x.goodbyeChannelId, 25);
        if ((welcomeEnabled && !await textChannel(welcomeChannelId)) || (goodbyeEnabled && !await textChannel(goodbyeChannelId))) return reply(422, { error: 'Welcome/goodbye channel invalid or inaccessible' });
        if ((welcomeEnabled || goodbyeEnabled) && !client.options.intents.has(GatewayIntentBits.GuildMembers)) return reply(422, { error: 'GuildMembers intent required' });
        if (autoModEnabled && !(client.options.intents.has(GatewayIntentBits.GuildMessages) && client.options.intents.has(GatewayIntentBits.MessageContent))) return reply(422, { error: 'GuildMessages and MessageContent intents required' });
        const spamLimit = Number(x.spamLimit), spamWindowSeconds = Number(x.spamWindowSeconds);
        if (!Number.isInteger(spamLimit) || spamLimit < 3 || spamLimit > 30 || !Number.isInteger(spamWindowSeconds) || spamWindowSeconds < 3 || spamWindowSeconds > 120) return reply(422, { error: 'Spam threshold invalid' });
        const blockedWords = Array.isArray(x.blockedWords) ? x.blockedWords.map(s => clean(s, 40).toLowerCase()).filter(Boolean).slice(0, 50) : [];
        const exemptRoleIds = Array.isArray(x.exemptRoleIds) ? x.exemptRoleIds.filter(idValid).slice(0, 30) : [];
        if (clean(x.welcomeText, 1000).length < 1 || clean(x.goodbyeText, 1000).length < 1) return reply(422, { error: 'Template cannot be empty' });
        state.config = { ...state.config, welcomeEnabled, goodbyeEnabled, autoModEnabled,
          welcomeChannelId, goodbyeChannelId, welcomeText: clean(x.welcomeText, 1000), goodbyeText: clean(x.goodbyeText, 1000),
          spamLimit, spamWindowSeconds, blockedWords, blockLinks: x.blockLinks === true, exemptRoleIds,
          ticketCategoryId: clean(x.ticketCategoryId, 25), ticketSupportRoleId: clean(x.ticketSupportRoleId, 25), selfRoles: state.config.selfRoles };
        record('settings.update', actor); await save(); return reply(200, { ok: true });
      }
      if (url.pathname === '/api/v4/publish-ticket') {
        const channel = await textChannel(clean(body.channelId, 25));
        if (!channel) return reply(422, { error: 'Channel invalid or inaccessible' });
        const message = await channel.send({ content: '## ✦ HOLLOWFANG SUPPORT\nButuh bantuan? Tekan tombol di bawah untuk membuka tiket pribadi.', components: [row('hf4:ticket:open', 'Buka Tiket')], allowedMentions: { parse: [] } });
        record('ticket.panel', actor, message.id); await save(); return reply(200, { ok: true, messageId: message.id });
      }
      if (url.pathname === '/api/v4/publish-role') {
        const channel = await textChannel(clean(body.channelId, 25));
        const roleId = body.roleId;
        const role = idValid(roleId) ? await guild.roles.fetch(roleId).catch(() => null) : null;
        if (!channel || !role || role.managed || !role.editable || role.id === guildId || restrictedSelfRole(role)) return reply(422, { error: 'Role cannot be self-assigned (permissions/hierarchy)' });
        if (!state.config.selfRoles.includes(role.id)) state.config.selfRoles.push(role.id);
        const message = await channel.send({ content: `## ✦ PILIH ROLE\nKlik tombol untuk memasang atau melepas role **${role.name.replaceAll('@', '＠')}**.`, components: [row(`hf4:role:${role.id}`, `Toggle ${role.name.slice(0, 50)}`)], allowedMentions: { parse: [] } });
        record('selfrole.panel', actor, message.id); await save(); return reply(200, { ok: true, messageId: message.id });
      }
      return reply(404, { error: 'Not found' });
    } catch (e) { console.error('[CommunityV4]', e instanceof Error ? e.name : 'Unknown'); return reply(502, { error: 'Discord operation failed' }); }
  });
  server.on('close', () => {
    client.off(Events.GuildMemberAdd, onJoin); client.off(Events.GuildMemberRemove, onLeave);
    client.off(Events.MessageCreate, onMessage); client.off(Events.InteractionCreate, onInteraction);
  });
  server.listen(port, '127.0.0.1', () => console.log(`[CommunityV4] 127.0.0.1:${port}`));
  return server;
}
