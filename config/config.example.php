<?php
// Copy this file to config.php and adjust for your machine.
return [
    // URL path the site lives under (no trailing slash). '' if served from the domain root.
    'base_path' => '/co-phuc-viet',
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'co_phuc_viet',
        'user'     => 'root',
        'password' => '',
    ],
    // Secret used to sign admin sessions/tokens — change to a long random string
    'app_secret' => 'change-me-to-a-long-random-string',
    'debug'      => true,
    'timezone'   => 'Asia/Ho_Chi_Minh',   // used by both PHP and MySQL (per connection)
    // Shop details printed on invoices / packing slips
    'store' => [
        'name'    => 'Cổ Phục Việt',
        'address' => 'Huế, Việt Nam',
        'phone'   => '0900 000 000',
        'email'   => 'lienhe@cophucviet.vn',
    ],
    // Shown to customers who pay by bank transfer
    'bank' => [
        'name'    => 'Vietcombank — CN Huế',
        'account' => '0000 0000 0000',
        'holder'  => 'CO PHUC VIET',
    ],
];
