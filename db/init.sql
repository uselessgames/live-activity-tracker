CREATE TABLE trackers (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    api_key TEXT NOT NULL UNIQUE
);

CREATE TABLE positions (
    id SERIAL PRIMARY KEY,
    tracker_id INTEGER REFERENCES trackers(id),
    lat DOUBLE PRECISION NOT NULL,
    lon DOUBLE PRECISION NOT NULL,
    time_recorded BIGINT NOT NULL,
    time_received BIGINT NOT NULL DEFAULT extract(epoch from now())::BIGINT
);

CREATE INDEX idx_positions_tracker_time ON positions (tracker_id, time_recorded);

CREATE TABLE activities (
    id SERIAL PRIMARY KEY,
    tracker_id INTEGER REFERENCES trackers(id),
    name TEXT UNIQUE NOT NULL,
    start_time BIGINT NOT NULL,
    end_time BIGINT NOT NULL,
    duration BIGINT NOT NULL,
    waypoints JSONB NOT NULL
);

INSERT INTO trackers (name, api_key) VALUES ('Test Tracker', 'test-key-123');
