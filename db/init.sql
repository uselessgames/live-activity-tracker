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
    reported_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_positions_tracker_time ON positions (tracker_id, reported_at);

INSERT INTO trackers (name, api_key) VALUES ('Test Tracker', 'test-key-123');
