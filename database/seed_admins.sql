-- Run ONCE in phpMyAdmin (SQL tab) after importing database.sql, then DELETE this file.
INSERT INTO users (name, email, password_hash, role)
VALUES
('Admin', 'mehtabstitching7777@gmail.com',
 '$2y$10$dmUqrtv4f.joTl7aqnsivuuDLzt8qXTI/Q/ihqo.lVTJmWZa5sh16', 'admin'),
('Admin', 'muhamamdmusabmehtab@gmail.com',
 '$2y$10$eq7e8SMVozDXhBLVLgoL5OY5AeidOJ5xxuv2wb92oa/wm2k8.SSTC', 'admin')
ON DUPLICATE KEY UPDATE role = 'admin', password_hash = VALUES(password_hash);
