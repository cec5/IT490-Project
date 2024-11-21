CREATE TABLE versionHistory(
	VersionId INT PRIMARY KEY AUTO_INCREMENT,
	BundleName varchar(255) NOT NULL,
	TestStatus ENUM('PASS', 'FAIL', 'NEW') NOT NULL,
    FilePath varchar(255) NOT NULL
)