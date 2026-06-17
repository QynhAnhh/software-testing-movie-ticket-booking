DROP DATABASE IF EXISTS movie_ticket_booking;
CREATE DATABASE IF NOT EXISTS movie_ticket_booking;

USE movie_ticket_booking;
DROP TABLE IF EXISTS auth;
CREATE TABLE IF NOT EXISTS auth(
	auth_id INT PRIMARY KEY AUTO_INCREMENT,
    auth_password VARCHAR(255) NOT NULL,
    auth_email VARCHAR(50) NOT NULL UNIQUE,
    auth_created_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS users;
CREATE TABLE IF NOT EXISTS users(
	user_id int PRIMARY KEY AUTO_INCREMENT,
	auth_id int UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(10) NOT NULL UNIQUE,
    Birth_date DATE NOT NULL,
    
    FOREIGN KEY (auth_id) 
		REFERENCES auth(auth_id)
		ON UPDATE CASCADE 
        ON DELETE CASCADE
);

DROP TABLE IF EXISTS roles;
CREATE TABLE IF NOT EXISTS roles(
	role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL
);

DROP TABLE IF EXISTS user_role;
CREATE TABLE IF NOT EXISTS user_role(
	user_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (user_id , role_id) ,
    
	FOREIGN KEY (user_id)
		REFERENCES users(user_id)
        ON DELETE CASCADE 
        ON UPDATE CASCADE ,
        
	FOREIGN KEY (role_id)
		REFERENCES roles(role_id)
        ON DELETE CASCADE 
        ON UPDATE CASCADE
);

DROP TABLE IF EXISTS movie;
CREATE TABLE IF NOT EXISTS movie(
	movie_id INT PRIMARY KEY AUTO_INCREMENT,
    movie_name VARCHAR(50) NOT NULL,
    movie_age INT NOT NULL,
    movie_content VARCHAR(100) NOT NULL,
    movie_country VARCHAR(50) NOT NULL,
    movie_year YEAR NOT NULL,
    movie_duration INT NOT NULL,
    movie_screening_date DATE NOT NULL
);

DROP TABLE IF EXISTS genre;
CREATE TABLE IF NOT EXISTS genre(
	genre_id INT PRIMARY KEY AUTO_INCREMENT,
    genre_name VARCHAR(50) UNIQUE NOT NULL
);

DROP TABLE IF EXISTS movie_genre;
CREATE TABLE IF NOT EXISTS movie_genre(
	movie_id INT NOT NULL,
    genre_id INT NOT NULL,
    PRIMARY KEY(movie_id , genre_id),
    
    FOREIGN KEY(movie_id)
		REFERENCES movie(movie_id)
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
        
	FOREIGN KEY (genre_id)
		REFERENCES genre(genre_id)
        ON DELETE CASCADE 
        ON UPDATE CASCADE
);

DROP TABLE IF EXISTS room;
CREATE TABLE IF NOT EXISTS room(
	room_id INT PRIMARY KEY AUTO_INCREMENT,
    room_name VARCHAR(50) NOT NULL UNIQUE,
    seat_count INT NOT NULL
);

DROP TABLE IF EXISTS show_time;
CREATE TABLE IF NOT EXISTS show_time(
	show_id INT PRIMARY KEY AUTO_INCREMENT,
    movie_id INT NOT NULL,
    room_id INT NOT NULL,
    show_start_time DATETIME NOT NULL,
    show_end_time DATETIME NOT NULL,
    show_bonus_price INT NOT NULL,
    
    FOREIGN KEY (movie_id)
		REFERENCES movie(movie_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        
	FOREIGN KEY (room_id)
		REFERENCES room(room_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

DROP TABLE IF EXISTS seat_type;
CREATE TABLE IF NOT EXISTS seat_type(
	seat_type_id INT PRIMARY KEY AUTO_INCREMENT,
    seat_type_name ENUM("REGULAR" , "VIP") NOT NULL,
    seat_type_price INT NOT NULL
);

DROP TABLE IF EXISTS seat;
CREATE TABLE IF NOT EXISTS seat(
	seat_id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL ,
    seat_number INT NOT NULL CHECK(seat_number BETWEEN 1 AND 12) ,
    seat_row ENUM("A","B","C","D","E","F","G","H") NOT NULL,
	seat_type_id INT NOT NULL ,
    
    UNIQUE(room_id, seat_row, seat_number),
    
    FOREIGN KEY (room_id)
		REFERENCES room(room_id)
        ON UPDATE CASCADE 
        ON DELETE CASCADE,
        
    FOREIGN KEY (seat_type_id)
		REFERENCES seat_type(seat_type_id)
        ON UPDATE CASCADE 
        ON DELETE CASCADE
);

DROP TABLE IF EXISTS booking;
CREATE TABLE IF NOT EXISTS booking(
	booking_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    booking_created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_price INT NOT NULL,
    
    FOREIGN KEY (user_id)
		REFERENCES users(user_id)
        ON UPDATE CASCADE 
        ON DELETE CASCADE
);

DROP TABLE IF EXISTS ticket;
CREATE TABLE IF NOT EXISTS ticket(
	ticket_id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    show_id INT NOT NULL,
    seat_id INT NOT NULL,
    ticket_price INT NOT NULL ,
    ticket_status ENUM('BOOKED', 'CANCELLED', 'USED') NOT NULL,
    UNIQUE (show_id , seat_id) ,
    
    FOREIGN KEY (booking_id) 
		REFERENCES booking(booking_id)
        ON UPDATE CASCADE 
        ON DELETE CASCADE,
        
	FOREIGN KEY (show_id)
		REFERENCES show_time(show_id)
        ON UPDATE CASCADE 
        ON DELETE CASCADE,
        
	FOREIGN KEY (seat_id)
		REFERENCES seat(seat_id)
        ON UPDATE CASCADE 
        ON DELETE CASCADE
);
    
    