/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

create database qly_chi_tieu;
use qly_chi_tieu;
drop database qly_chi_tieu;

			-- USER --
CREATE TABLE users (
    uid INT auto_increment PRIMARY KEY,
    uname NVARCHAR(255) NOT NULL ,
    uemail NVARCHAR(255) NOT NULL UNIQUE,
    upwd VARCHAR(255) NOT NULL,
	u_create_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    urole Enum('admin','user') not null default "user"
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
insert into users values ('1','admin','admin@gmail.com','admin',now(),'admin');
CREATE TABLE users_detail (
	stt INT auto_increment PRIMARY KEY,
    uid INT not null unique,
    uphone varchar(15),
    ugender enum('Nam', 'Nữ', 'Khác') DEFAULT 'Khác',
    u_birthday date,
    uimage varchar(100),
    
    foreign key (uid) references users(uid) on delete cascade
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
insert into users_detail values ('1', '2', 'Chưa cập nhật', 'Khác', '2002-07-06', 'avata_default.jpg');
CREATE TABLE categories (
    category_id INT auto_increment PRIMARY KEY,
    uid int not null,
    category_name NVARCHAR(255) NOT NULL,
    category_note text,
    category_type enum('Thu nhập','Chi tiêu') not null,
    category_icon varchar(50) not null default '+',
    is_system TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    unique key unique_category(uid, category_name, category_type, is_system),
    foreign key (uid) references users(uid) on delete cascade
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

create table  transactions  (
	transaction_id  int auto_increment primary key,
    uid int not null,
    category_id int not null,
	transaction_amount decimal(12,2) not null ,
    transaction_note  text,
    transaction_type enum('Thu nhập','Chi tiêu') not null,
    transaction_date date not null,
    transaction_created_at timestamp default  current_timestamp,
    
    foreign key (uid) references users(uid) on delete cascade,
    foreign key (category_id) references categories(category_id) on delete cascade
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

insert into users values
('2','dien','dien@gmail.com','1234',NOW(),'user'),
('3','dang','dang@gmail.com','1',NOW(),'user');


INSERT INTO categories (uid, category_name, category_type, category_icon, is_system) VALUES
(1, 'Lương', 'Thu nhập', 'bx bxs-credit-card', 1),
(1, 'Thưởng', 'Thu nhập', 'fa-solid fa-sack-dollar', 1),
(1, 'Quà tặng', 'Thu nhập', 'bx bxs-gift', 1),
(1, 'Khác', 'Thu nhập', 'bx bxl-slack-old', 1),
(1, 'Ăn uống', 'Chi tiêu', 'bx bx-restaurant', 1),
(1, 'Khác', 'Chi tiêu', 'bx bxl-slack-old', 1),
(1, 'Sức khỏe', 'Chi tiêu', 'bx bxs-first-aid', 1),
(1, 'Giải trí', 'Chi tiêu', 'bx bxs-movie-play', 1),
(1, 'Mua sắm', 'Chi tiêu', 'bx bxs-basket', 1),
(1, 'Giáo dục', 'Chi tiêu', 'bx bxs-book-bookmark', 1),
(1, 'Thể thao', 'Chi tiêu', 'bx bx-swim', 1),
(1, 'Di chuyển', 'Chi tiêu', 'bx bx-train', 1),
(1, 'Gia đình', 'Chi tiêu', 'bx bx-male-female', 1),
(1, 'Du lịch', 'Chi tiêu', 'bx bxs-plane-alt', 1),
(1, 'Thú cưng', 'Chi tiêu', 'bx bxs-cat', 1),
(1, 'Nhà ở', 'Chi tiêu', 'bx bxs-building', 1),
(1, 'Học phí', 'Chi tiêu', 'bx bxs-graduation', 1);
UPDATE categories SET category_icon = 'bx bxs-graduation' WHERE (`uid` = '1' and `category_name` = 'Học phí');


INSERT INTO categories (uid, category_name, category_type, category_icon, is_system) VALUES
(2, 'Tiền học bổng', 'Thu nhập', 'bx bx-book', 0),
(2, 'Tiền cafe', 'Chi tiêu', 'bx bx-coffee', 0);

INSERT INTO categories (uid, category_name, category_type, category_icon, is_system) VALUES
(3, 'Tiền làm thêm', 'Thu nhập', 'bx bx-briefcase', 0),
(3, 'Mua sách', 'Chi tiêu', 'bx bx-book-open', 0);

SELECT c.*, u.uname as owner_name 
                FROM categories c left join users u on c.uid = u.uid
                WHERE (c.is_system = 1 or c.uid = 2) AND c.category_id = 20;
SELECT t.*, c.category_name, c.category_icon
		FROM transactions t
		LEFT JOIN categories c ON t.category_id = c.category_id
		WHERE t.category_id = 20 AND t.uid = 2
		ORDER BY t.transaction_date DESC, t.transaction_created_at DESC;
ALTER TABLE users ADD reset_token VARCHAR(255) NULL;

select * from users;
select * from users_detail;
select * from categories;
select * from transactions;
UPDATE users SET urole = 'user' WHERE (`uid` = '1');
UPDATE users SET u_create_at = NOW() WHERE (`uid` = '1');
drop table users_detail;
drop table users;
drop table categories;
delete from transactions where uid = 2 and transaction_id = 8 and category_id = 12;