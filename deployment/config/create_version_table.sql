CREATE TABLE versionHistory(
	VersionId INT,
	BundleName varchar(255) NOT NULL,
	TestStatus ENUM('PASS', 'FAIL', 'NEW') NOT NULL,
    FilePath varchar(255) NOT NULL,
	TargetMachine enum('FRONTEND', 'DMZ', 'BACKEND', 'DEPLOYMENT'),
	PRIMARY KEY (BundleName, VersionId)
)