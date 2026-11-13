<?php

return [
    'max_kib' => min(10240, max(1, (int) env('MEDIA_MAX_KIB', 5120))),
];
