<?php

return [
    'owner_discord_ids' => array_values(
        array_filter(
            array_map(
                'trim',
                explode(
                    ',',
                    (string) env('HOLLOWFANG_OWNER_DISCORD_IDS', '')
                )
            )
        )
    ),
];
