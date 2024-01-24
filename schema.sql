CREATE TABLE positions (
    name VARCHAR(100) PRIMARY KEY,
    hourly_rate INT CHECK (hourly_rate > 0)
);

CREATE TABLE employees (
    name VARCHAR(200) PRIMARY KEY,
    POSITION VARCHAR(100),
    CONSTRAINT position FOREIGN KEY (position) REFERENCES positions (name)
);

CREATE TABLE timesheet (
    id SERIAL PRIMARY KEY,
    employee VARCHAR(200),
    task VARCHAR(200),
    start_time TIMESTAMP WITHOUT TIME ZONE,
    end_time TIMESTAMP WITHOUT TIME ZONE,
    CONSTRAINT employee FOREIGN KEY (employee) REFERENCES employees (name)
);
