CREATE TABLE Branch (branchNo VARCHAR(4), street VARCHAR(25), city VARCHAR(15), postcode VARCHAR(8));

CREATE TABLE Staff (staffNo VARCHAR(5), fName VARCHAR(20), lName VARCHAR(20), position VARCHAR(20), sex VARCHAR(1), DOB DATE, salary DECIMAL(7,2), branchNo VARCHAR(4));

CREATE TABLE PropertyForRent (propertyNo VARCHAR(5), street VARCHAR(25), city VARCHAR(20), postcode VARCHAR(8), type VARCHAR(10), rooms INT, rent INT, ownerNo VARCHAR(5), staffNo VARCHAR(5), branchNo VARCHAR(5));

CREATE TABLE Client (clientNo VARCHAR(5), fName VARCHAR(20), lName VARCHAR(20), telNo VARCHAR(18), prefType VARCHAR(10), maxRent INT, email VARCHAR(30));

CREATE TABLE PrivateOwner (ownerNo VARCHAR(5), fName VARCHAR(20), lName VARCHAR(20), address VARCHAR(25), telNo VARCHAR(18), email VARCHAR(30), password VARCHAR(40));

CREATE TABLE Viewing (clientNo VARCHAR(5), propertyNo VARCHAR(5), viewDate DATE, comment VARCHAR(128));

INSERT INTO Branch VALUES
('B005', '22 Deer Rd', 'London', 'SW1 4EH'),
('B003', '163 Main St', 'Glasgow', 'G11 9QX'),
('B004', '', 'Bristol', ''),
('B002', '56 Clover Dr', 'London', 'NW10 6EU');

INSERT INTO Staff VALUES 
('SL21', 'John', 'White', 'Manager', 'M', '1945-10-01', 30000.00, 'B005'),
('SG37', 'Ann', 'Beech', 'Assistant', 'F', '1960-11-10', 12000.00, 'B003'),
('SG14', 'David', 'Ford', 'Supervisor', 'M', '1958-03-24', 18000.00, 'B003'),
('SA9', 'Mary', 'Howe', 'Assistant', 'F', '1970-02-19', 9000.00, 'B007'),
('SG5', 'Susan', 'Brand', 'Manager', 'F', '1940-06-03', 24000.00, 'B003'),
('SL41', 'Julie', 'Lee', 'Assistant', 'F', '1965-06-13', 9000.00, 'B005');

INSERT INTO PropertyForRent VALUES 
('PA14', '16 Holhead', 'Aberdeen', 'AB7 5SU', 'House', 6, 650, 'CO46', 'SA9', 'B007'),
('PG4', '', 'Glasgow', '', 'Flat', 3, 350, '', '', 'B003'),
('PG16', '5 Novar Dr', 'Glasgow', 'G12 9AX', 'Flat', 4, 450, '', 'SG14', 'B003'),
('PG36', '2 Manor Rd', 'Glasgow', 'G32 4QX', 'Flat', 3, 375, '', 'SG37', 'B003'),
('PG21', '18 Dale Rd', 'Glasgow', 'G12', 'House', 5, 600, '', 'SG37', 'B003'),
('PL94', '', 'London', '', 'Flat', 4, 400, '', 'SL41', 'B005');

INSERT INTO Client (clientNo, fName, lName) VALUES
('CR56', 'Aline', 'Stewart'),
('CR52', 'Mary', 'Tregear'),
('CR76', 'John', 'Kay');

INSERT INTO Viewing VALUES
('CR56', 'PG36', '', ''),
('CR56', 'PA14', '', 'too small'),
('CR56', 'PG4', '', ''),
('CR62', 'PA14', '', 'no dining room'),
('CR76', 'PG4', '', 'too remote');