<?php

return [
    'circuit_breaker_threshold' => (int) env('CIRCUIT_BREAKER_THRESHOLD', 10),
    'circuit_breaker_window_minutes' => (int) env('CIRCUIT_BREAKER_WINDOW_MINUTES', 5),
    'malformed_line_alert_threshold' => (float) env('MALFORMED_LINE_ALERT_THRESHOLD', 0.5),
];
