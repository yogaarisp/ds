-- SQL DELTA UPDATE: Harga Paket Dimsum Sarang
-- Generated on: 2026-04-14 22:54:54
-- Deskripsi: Menaikkan harga Paket (Mix 1, 2, 3, 5 & Paket 5pcs)

BEGIN;

UPDATE package_combinations SET base_price = 35000 WHERE id = 1; -- Mix 2 Varian > Ayam Original + Ayam Udang
UPDATE package_combinations SET base_price = 35000 WHERE id = 2; -- Mix 2 Varian > Ayam Original + Ayam Keju
UPDATE package_combinations SET base_price = 36000 WHERE id = 3; -- Mix 2 Varian > Ayam Udang + Ayam Keju
UPDATE package_combinations SET base_price = 36000 WHERE id = 4; -- Mix 2 Varian > Ayam Pedas + Ayam Keju
UPDATE package_combinations SET base_price = 33000 WHERE id = 6; -- Mix 1 Varian > Dimsum Ayam Original
UPDATE package_combinations SET base_price = 17000 WHERE id = 7; -- Paket 5 pcs > Dimsum Ayam Original
UPDATE package_combinations SET base_price = 35000 WHERE id = 10; -- Mix 1 Varian > Dimsum Udang
UPDATE package_combinations SET base_price = 36000 WHERE id = 11; -- Mix 1 Varian > Dimsum Keju
UPDATE package_combinations SET base_price = 20000 WHERE id = 12; -- Paket 5 pcs > Dimsum Udang
UPDATE package_combinations SET base_price = 35000 WHERE id = 13; -- Mix 1 Varian > Dimsum Pedas
UPDATE package_combinations SET base_price = 31000 WHERE id = 14; -- Mix 1 Varian > Dimsum Kol
UPDATE package_combinations SET base_price = 36000 WHERE id = 15; -- Mix 1 Varian > Dimsum Nori
UPDATE package_combinations SET base_price = 35000 WHERE id = 16; -- Mix 1 Varian > Gyoza
UPDATE package_combinations SET base_price = 46000 WHERE id = 17; -- Mix 1 Varian > Udang Utuh
UPDATE package_combinations SET base_price = 48000 WHERE id = 18; -- Mix 1 Varian > Udang Utuh Pedas
UPDATE package_combinations SET base_price = 36000 WHERE id = 19; -- Mix 3 Varian > Ayam + Keju + Udang
UPDATE package_combinations SET base_price = 20000 WHERE id = 21; -- Paket 5 pcs > Dimsum Ayam Pedas
UPDATE package_combinations SET base_price = 21000 WHERE id = 22; -- Paket 5 pcs > Dimsum Ayam Keju
UPDATE package_combinations SET base_price = 21000 WHERE id = 23; -- Paket 5 pcs > Dimsum Nori
UPDATE package_combinations SET base_price = 20000 WHERE id = 24; -- Paket 5 pcs > Gyoza
UPDATE package_combinations SET base_price = 17000 WHERE id = 25; -- Paket 5 pcs > Kol
UPDATE package_combinations SET base_price = 25000 WHERE id = 26; -- Paket 5 pcs > Udang Utuh
UPDATE package_combinations SET base_price = 26000 WHERE id = 27; -- Paket 5 pcs > Udang Utuh Pedas
UPDATE package_combinations SET base_price = 39000 WHERE id = 28; -- Mix 5 Varian > Mix 5 Varian G
UPDATE package_combinations SET base_price = 43000 WHERE id = 29; -- Mix 5 Varian > Mix 5 Varian Udang Utuh

COMMIT;
