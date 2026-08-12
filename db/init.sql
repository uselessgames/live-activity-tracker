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
    time_received BIGINT NOT NULL DEFAULT extract(epoch from now())::BIGINT,
    speed_calculated DOUBLE PRECISION
);

CREATE INDEX idx_positions_tracker_time ON positions (tracker_id, time_recorded);

INSERT INTO trackers (name, api_key) VALUES ('Test Tracker', 'test-key-123');