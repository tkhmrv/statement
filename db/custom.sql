USE u3119786_statement_db;

-- Create tables
CREATE TABLE Messengers (
	IdMessenger INT AUTO_INCREMENT PRIMARY KEY,
    Messenger VARCHAR(100) NOT NULL
);

CREATE TABLE FeedbackForm (
    IdFeedback INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Phone VARCHAR(12) NOT NULL,
    MessengerId INT NOT NULL,
    Topic VARCHAR(255) NOT NULL,
    Message TEXT NOT NULL,
    PrivacyCheckbox BOOLEAN NOT NULL,
    SentAt datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (MessengerId) REFERENCES Messengers(IdMessenger) ON DELETE CASCADE
);

CREATE TABLE TgChats (
    IdTgChat INT AUTO_INCREMENT PRIMARY KEY,
    ChatId BIGINT NOT NULL UNIQUE,
    ChatTitle VARCHAR(255) DEFAULT NULL,
    ChatIdAddedAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    LastMessageSuccessAt DATETIME DEFAULT NULL,
    ErrorCount INT DEFAULT 0
);


-- Insert data into tables
INSERT INTO Messengers (Messenger)
VALUES ("Telegram");
INSERT INTO Messengers (Messenger)
VALUES ("WhatsApp");
а не