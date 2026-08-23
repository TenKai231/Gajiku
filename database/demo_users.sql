USE penggajian_db;

INSERT INTO users (username, password, role)
VALUES
    ('admin', '$2y$12$EmLWFcSUpU7cyQyEEo3DTe4BipF2XE/t57K5WfiRW7AOEBU0/fBmi', 'ADMIN'),
    ('hrd', '$2y$12$GanuIYv.xyRskDG180iWKuT40Z60XKzR43KT2C8sFdcdHN2l97zRK', 'HR')
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    role = VALUES(role);
