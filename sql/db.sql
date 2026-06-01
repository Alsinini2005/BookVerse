SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS dbproj_ratings;
DROP TABLE IF EXISTS dbproj_comments;
DROP TABLE IF EXISTS dbproj_books;
DROP TABLE IF EXISTS dbproj_users;
SET FOREIGN_KEY_CHECKS = 1;


CREATE TABLE dbproj_users (
    user_id    INT          AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARBINARY(256) NOT NULL,
    role       ENUM('admin','creator','visitor') NOT NULL DEFAULT 'visitor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE dbproj_books (
    book_id     INT          AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(150) NOT NULL,
    author      VARCHAR(100) NOT NULL,
    category    VARCHAR(50)  NOT NULL,
    description TEXT         NOT NULL,
    cover_image MEDIUMTEXT,
    media_file  VARCHAR(255),
    creator_id  INT          NOT NULL,
    status      ENUM('draft','published') NOT NULL DEFAULT 'draft',
    views       INT          NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES dbproj_users(user_id) ON DELETE CASCADE
);

CREATE TABLE dbproj_comments (
    comment_id   INT  AUTO_INCREMENT PRIMARY KEY,
    book_id      INT  NOT NULL,
    user_id      INT  NOT NULL,
    comment_text TEXT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES dbproj_books(book_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES dbproj_users(user_id) ON DELETE CASCADE
);

CREATE TABLE dbproj_ratings (
    rating_id    INT AUTO_INCREMENT PRIMARY KEY,
    book_id      INT NOT NULL,
    user_id      INT NOT NULL,
    rating_value INT NOT NULL CHECK (rating_value BETWEEN 1 AND 5),
    FOREIGN KEY (book_id) REFERENCES dbproj_books(book_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES dbproj_users(user_id) ON DELETE CASCADE,
    UNIQUE (book_id, user_id)
);


CREATE FULLTEXT INDEX idx_fulltext_title ON dbproj_books(title);
CREATE INDEX idx_creator    ON dbproj_books(creator_id);
CREATE INDEX idx_created_at ON dbproj_books(created_at);
CREATE INDEX idx_status     ON dbproj_books(status);


CREATE OR REPLACE VIEW popular_books AS
SELECT
    b.book_id,
    b.title,
    b.category,
    b.views,
    u.full_name              AS creator_name,
    COUNT(r.rating_id)       AS total_ratings,
    ROUND(AVG(r.rating_value), 1) AS average_rating
FROM dbproj_books b
LEFT JOIN dbproj_ratings r ON b.book_id   = r.book_id
LEFT JOIN dbproj_users   u ON b.creator_id = u.user_id
WHERE b.status = 'published'
GROUP BY b.book_id;


DROP PROCEDURE IF EXISTS GetBooksByCreator;

DELIMITER $$
CREATE PROCEDURE GetBooksByCreator(IN creatorID INT)
BEGIN
    SELECT b.book_id, b.title, b.author, b.category,
           b.status, b.views, b.created_at,
           ROUND(AVG(r.rating_value),1) AS avg_rating
    FROM dbproj_books b
    LEFT JOIN dbproj_ratings r ON b.book_id = r.book_id
    WHERE b.creator_id = creatorID
    GROUP BY b.book_id
    ORDER BY b.created_at DESC;
END $$
DELIMITER ;


DROP TRIGGER IF EXISTS trg_trim_comment;

DELIMITER $$
CREATE TRIGGER trg_trim_comment
BEFORE INSERT ON dbproj_comments
FOR EACH ROW
BEGIN
    SET NEW.comment_text = TRIM(NEW.comment_text);
    IF LENGTH(NEW.comment_text) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Comment text cannot be empty.';
    END IF;
END $$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_rating_check;

DELIMITER $$
CREATE TRIGGER trg_rating_check
BEFORE INSERT ON dbproj_ratings
FOR EACH ROW
BEGIN
    DECLARE book_status VARCHAR(20);
    SELECT status INTO book_status FROM dbproj_books WHERE book_id = NEW.book_id;
    IF book_status != 'published' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot rate an unpublished book.';
    END IF;
END $$
DELIMITER ;

INSERT INTO dbproj_users (full_name, email, password, role) VALUES
('Admin User',   'admin@bookverse.com', AES_ENCRYPT('Admin1234!',  'bv_key'), 'admin'),
('John Creator', 'john@bookverse.com',  AES_ENCRYPT('John1234!',   'bv_key'), 'creator'),
('Sara Creator', 'sara@bookverse.com',  AES_ENCRYPT('Sara1234!',   'bv_key'), 'creator'),
('Ali Visitor',  'ali@bookverse.com',   AES_ENCRYPT('Ali12345!',   'bv_key'), 'visitor'),
('Mona Visitor', 'mona@bookverse.com',  AES_ENCRYPT('Mona1234!',   'bv_key'), 'visitor'),
('Omar Visitor', 'omar@bookverse.com',  AES_ENCRYPT('Omar1234!',   'bv_key'), 'visitor');

INSERT INTO dbproj_books (title, author, category, description, cover_image, media_file, creator_id, status, views, created_at) VALUES
('Clean Code',                       'Robert C. Martin',      'Programming', 'This book teaches developers how to write code that is easy to read and maintain. It covers topics such as choosing good variable names, writing short focused functions, and how to refactor messy code into something clean and professional.',                                'clean_code.jpg',      'clean_code.pdf',      2, 'published', 320, '2025-01-05 10:00:00'),
('The Pragmatic Programmer',         'David Thomas',          'Programming', 'A well-known guide for software developers that covers career advice and technical skills. It introduces concepts like the DRY principle, code automation, and how to think about software development as a craft rather than just a job.',                                      'pragmatic.jpg',       'pragmatic.pdf',       2, 'published', 275, '2025-01-10 11:00:00'),
('JavaScript: The Good Parts',       'Douglas Crockford',     'Programming', 'The author selects the most reliable and useful parts of JavaScript and explains how to use them effectively. The book also warns about which parts of the language to avoid and why JavaScript behaves differently from other languages.',                                     'js_good_parts.jpg',   'js_good_parts.pdf',   2, 'published', 210, '2025-01-15 09:00:00'),
('Python Crash Course',              'Eric Matthes',          'Programming', 'A beginner-friendly book that teaches Python programming through simple explanations and hands-on projects. By the end, readers build a simple game, create data charts, and develop a basic web application using the skills they learned.',                                    'python_crash.jpg',    'python_crash.pdf',    2, 'published', 190, '2025-01-20 14:00:00'),
('You Dont Know JS',                 'Kyle Simpson',          'Programming', 'A series that goes deep into how JavaScript actually works under the hood. It explains confusing topics like scope, closures, the this keyword, and prototypes in a way that helps developers truly understand the language instead of just guessing.',                          'ydkjs.jpg',           'ydkjs.pdf',           2, 'published', 165, '2025-01-25 10:30:00'),
('HTML and CSS: Design and Build',   'Jon Duckett',           'Programming', 'A visually rich book that introduces web design through clear diagrams and colour-coded examples. It covers how to structure web pages with HTML5, style them with CSS3, and create layouts that work well on both desktop and mobile screens.',                               'html_css.jpg',        'html_css.pdf',        2, 'published', 145, '2025-02-01 08:00:00'),
('Learning MySQL',                   'Seyed Tahaghoghi',      'Database',    'A step-by-step introduction to working with MySQL databases. The book explains how to write SQL queries, create and manage tables, use joins, and work with stored procedures and triggers for real web application development.',                                               'learning_mysql.jpg',  'learning_mysql.pdf',  3, 'published', 230, '2025-02-05 10:00:00'),
('Database Design for Mere Mortals', 'Michael J. Hernandez', 'Database',    'This book explains how to design a relational database from scratch without assuming any prior knowledge. It walks through identifying tables, setting primary and foreign keys, defining relationships, and applying normalisation to remove duplicate data.',                 'db_design.jpg',       'db_design.pdf',       3, 'published', 185, '2025-02-10 11:00:00'),
('The Design of Everyday Things',    'Don Norman',            'Design',      'A famous book about why some products are easy to use and others are frustrating. The author uses everyday examples like doors and phones to explain design principles such as affordance, feedback, and how to match a product to the way people think.',                        'design_everyday.jpg', 'design_everyday.pdf', 3, 'published', 160, '2025-02-15 09:30:00'),
('Refactoring UI',                   'Adam Wathan',           'Design',      'A practical guide aimed at developers who want to improve the visual quality of their user interfaces. It gives direct advice on choosing colours, spacing elements, picking fonts, and building components that look polished without needing formal design training.',          'refactoring_ui.jpg',  'refactoring_ui.pdf',  3, 'published', 195, '2025-02-20 14:00:00'),
('The Innovators',                   'Walter Isaacson',       'Technology',  'A detailed history of the people who built the modern digital world. The book tells the stories of the teams behind computers, programming languages, the internet, and personal devices, showing how collaboration and creativity drove each major breakthrough.',                'innovators.jpg',      'innovators.pdf',      2, 'published', 140, '2025-03-01 10:00:00'),
('Cybersecurity Essentials',         'Charles J. Brooks',     'Security',    'An introductory book covering the core ideas behind protecting computer systems and networks. Topics include common attack types, how firewalls and encryption work, identity management, and the steps organisations take to keep their data and systems secure.',               'cybersec.jpg',        'cybersec.pdf',        3, 'published', 175, '2025-03-05 11:00:00'),
('Hacking: The Art of Exploitation', 'Jon Erickson',          'Security',    'A technical book that explains how software vulnerabilities are found and exploited. It covers low-level programming, memory management, shellcode, and network attacks, helping developers understand security risks so they can write better-protected software.',              'hacking.jpg',         'hacking.pdf',         3, 'published', 155, '2025-03-10 09:00:00'),
('Good to Great',                    'Jim Collins',           'Business',    'A business research book that studied companies which made a lasting jump from average performance to outstanding results. The findings show that strong leadership, the right team, and focusing on what you do best are more important than technology or market conditions.',    'good_great.jpg',      'good_great.pdf',      2, 'published', 130, '2025-03-15 10:00:00'),
('Zero to One',                      'Peter Thiel',           'Business',    'A book about startups and how to build a business that creates something genuinely new rather than copying what already exists. The author argues that real progress comes from unique ideas and that the best companies solve problems no one else is trying to solve.',         'zero_one.jpg',        'zero_one.pdf',        3, 'published', 120, '2025-03-20 11:00:00');

INSERT INTO dbproj_comments (book_id, user_id, comment_text, created_at) VALUES
(1,  4, 'Clean Code completely changed how I write and review code. The chapter on meaningful names alone is worth the read.',        '2025-01-10 12:00:00'),
(1,  5, 'Every developer should read this. It takes time to apply everything but the results are worth it.',                          '2025-01-11 09:00:00'),
(2,  6, 'The Pragmatic Programmer is timeless. I keep coming back to it every year and always find something new.',                   '2025-01-12 10:00:00'),
(3,  4, 'Crockford really knows how to cut through the complexity. This book helped me stop fighting JavaScript and start using it.', '2025-01-16 14:00:00'),
(4,  5, 'Best Python book for beginners. The projects at the end are fun and really cement the concepts.',                            '2025-01-21 11:00:00'),
(4,  6, 'I finished the game project and it was so satisfying. Great book to start with.',                                            '2025-01-22 08:00:00'),
(6,  4, 'Ducketts visual style makes HTML and CSS so much easier to understand than other books.',                                  '2025-02-02 10:00:00'),
(7,  5, 'Solid MySQL reference. I use it alongside official docs when designing schemas.',                                            '2025-02-06 09:00:00'),
(8,  6, 'Finally understood normalisation properly after reading this. Very clear explanations.',                                     '2025-02-11 11:00:00'),
(9,  4, 'Don Normans observations about everyday objects are brilliant. Changed how I look at design.',                             '2025-02-16 10:00:00'),
(10, 5, 'Refactoring UI is the most practical design book I have read. Immediately applicable.',                                      '2025-02-21 14:00:00'),
(12, 6, 'Great introduction to cybersecurity. Covers a wide range of topics without being too shallow.',                              '2025-03-06 10:00:00');

INSERT INTO dbproj_ratings (book_id, user_id, rating_value) VALUES
(1,  4, 5),
(1,  5, 5),
(1,  6, 4),
(2,  4, 5),
(2,  5, 4),
(3,  6, 4),
(4,  4, 5),
(4,  5, 5),
(4,  6, 4),
(5,  4, 4),
(6,  5, 5),
(7,  6, 4),
(8,  4, 4),
(9,  5, 5),
(10, 6, 5),
(11, 4, 4),
(12, 5, 5),
(13, 6, 4),
(14, 4, 4),
(15, 5, 4);
