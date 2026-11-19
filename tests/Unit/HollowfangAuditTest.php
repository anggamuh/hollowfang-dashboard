<?php

namespace Tests\Unit;

use App\Support\HollowfangAudit;
use PHPUnit\Framework\TestCase;

class HollowfangAuditTest extends TestCase
{
    private array $records = [
        ['at' => '2026-11-19T09:00:00Z', 'action' => 'moderation.ban', 'actor' => '111111111111111111', 'target' => '222222222222222222', 'detail' => 'Spam'],
        ['at' => '2026-11-19T09:02:00Z', 'action' => 'role.add', 'actor' => '111111111111111111', 'target' => '333333333333333333', 'detail' => 'Member'],
        ['at' => '2026-11-19T09:05:00Z', 'action' => 'event.created', 'actor' => '111111111111111111', 'target' => '', 'detail' => 'Game night'],
    ];

    public function test_search_and_category_filters_are_combined(): void
    {
        $this->assertCount(3, HollowfangAudit::filter($this->records));
        $this->assertCount(1, HollowfangAudit::filter($this->records, 'SPAM', 'moderation'));
        $this->assertSame('role.add', HollowfangAudit::filter($this->records, '333333333333333333')[0]['action']);
        $this->assertSame([], HollowfangAudit::filter($this->records, 'SPAM', 'event'));
        $this->assertCount(3, HollowfangAudit::filter($this->records, '', 'unknown'));
        $this->assertCount(1, HollowfangAudit::filter([...$this->records, 'untrusted'], '', 'moderation'));
    }

    public function test_csv_escapes_spreadsheet_formulas_and_preserves_discord_ids(): void
    {
        $this->assertSame("'=1+1", HollowfangAudit::safeCell('=1+1'));
        $this->assertSame("'  +SUM(1,1)", HollowfangAudit::safeCell('  +SUM(1,1)'));
        $this->assertSame("'@danger", HollowfangAudit::safeCell('@danger'));
        $this->assertSame("'111111111111111111", HollowfangAudit::safeCell('111111111111111111'));
        $this->assertSame("text here", HollowfangAudit::safeCell("text\r\nhere"));
        $this->assertSame('ok', HollowfangAudit::safeCell('ok'));
    }

    public function test_csv_writes_bom_header_and_sanitized_records(): void
    {
        $stream = fopen('php://temp', 'w+');
        $this->assertIsResource($stream);
        HollowfangAudit::writeCsv($stream, [[
            'at' => '2026-11-19T09:00:00Z',
            'action' => 'moderation.warn',
            'actor' => '111111111111111111',
            'target' => '222222222222222222',
            'detail' => '=HYPERLINK("bad")',
        ]]);
        rewind($stream);
        $bytes = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $bytes);
        $this->assertStringContainsString('Actor Discord ID', $bytes);
        $this->assertStringContainsString("'=HYPERLINK", $bytes);
        $this->assertStringContainsString("'111111111111111111", $bytes);
    }
}
