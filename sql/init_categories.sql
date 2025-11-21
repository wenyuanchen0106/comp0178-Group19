INSERT INTO categories (category_id, category_name)
VALUES 
    (1, 'Books'),
    (2, 'Electronics'),
    (3, 'Clothing')
ON DUPLICATE KEY UPDATE
    category_name = VALUES(category_name);
