CREATE TABLE positions (
    position_id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE,
    hourly_rate INT CHECK (hourly_rate > 0)
);

CREATE TABLE employees (
    employee_id SERIAL PRIMARY KEY,
    name VARCHAR(200),
    position_id SERIAL,
    CONSTRAINT position_id FOREIGN KEY (position_id) REFERENCES positions (position_id)
);

CREATE TABLE timesheet (
    id SERIAL PRIMARY KEY,
    employee_id SERIAL,
    task VARCHAR(200),
    start_time TIMESTAMP WITHOUT TIME ZONE,
    end_time TIMESTAMP WITHOUT TIME ZONE,
    CONSTRAINT employee_id FOREIGN KEY (employee_id) REFERENCES employees (employee_id)
);