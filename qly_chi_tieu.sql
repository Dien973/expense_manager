/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

create database qly_chi_tieu;
use qly_chi_tieu;

			-- USER --
CREATE TABLE users (
    uid INT auto_increment PRIMARY KEY,
    uname NVARCHAR(255) NOT NULL ,
    uemail NVARCHAR(255) NOT NULL UNIQUE,
    upwd NVARCHAR(255) NOT NULL,
	u_create_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    -- token varchar(200) DEFAULT NULL,
    urole varchar(30) not null default "user"
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE users_detail (
	stt INT auto_increment PRIMARY KEY,
    uid INT not null,
    uphone char(10) not null,
    ugender NVARCHAR(10) not null,  -- CHECK (ugender IN ('Nam', 'Nữ', 'Khác')) DEFAULT 'Khác' --,
    u_birthday date not null,
    uimage varchar(100) not null,
    
    foreign key (uid) references users(uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

 -- -------------insert du lieu -------------
insert into users
values('','dien','dien@gmail.com','1234',NOW(),'user');
insert into users
values('','admin','admin@gmail.com','admin',NOW(),'admin');


ALTER TABLE users ADD reset_token VARCHAR(255) NULL;

select * from users_detail;
UPDATE users SET urole = 'user' WHERE (`uid` = '1');
UPDATE users SET u_create_at = NOW() WHERE (`uid` = '1');
drop table users_detail;
		-- ORTHERS --
CREATE TABLE categories (
    category_id INT auto_increment PRIMARY KEY,
    category_name NVARCHAR(255) NOT NULL UNIQUE,
    category_description text,
    category_create_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE expenses (
    expense_id INT auto_increment PRIMARY KEY,
    user_id INT,
    category_id INT,
    expense_amount DECIMAL(10,2) NOT NULL,
    expense_description text,
    expense_type NVARCHAR(10) CHECK (expense_type IN ('Income', 'Expense')) NOT NULL,
    expense_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

CREATE TABLE budgets (
    budget_id INT auto_increment PRIMARY KEY,
    user_id INT,
    category_id INT,
    budget_amount DECIMAL(10,2) NOT NULL,
    budget_description text,
    start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_date DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);