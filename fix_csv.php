<?php
$lines = file('user_split.csv');
$output = fopen('user_split_fixed.csv', 'w');

// Renamed 'firstname' to 'nama_siswa' to satisfy the validator
fputcsv($output, ['nis', 'password', 'nama_siswa', 'lastname', 'email']);

for ($i = 2; $i < count($lines); $i++) {
    $row = str_getcsv($lines[$i]);
    if (count($row) < 4) continue;
    
    $nis = $row[0];
    $password = $row[1];
    $firstname = $row[2] ?? '';
    $lastname = $row[3] ?? '';
    $email = $row[4] ?? '';
    
    fputcsv($output, [$nis, $password, $firstname, $lastname, $email]);
}

fclose($output);
echo "Fixed CSV created: user_split_fixed.csv\n";
