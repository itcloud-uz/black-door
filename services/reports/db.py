"""
Black Door — Database Connection Helper
Supports both psycopg2 (PostgreSQL) and sqlite3 (SQLite).
"""

import os
import sqlite3
import psycopg2
from psycopg2 import pool
from contextlib import contextmanager
from dotenv import load_dotenv

# Load .env from root directory
load_dotenv(os.path.join(os.path.dirname(__file__), "..", "..", ".env"))

_connection_pool: pool.ThreadedConnectionPool | None = None
_sqlite_conn: sqlite3.Connection | None = None


def is_sqlite() -> bool:
    return os.getenv("DB_CONNECTION", "pgsql") == "sqlite"


def get_sqlite_path() -> str:
    path = os.getenv("DB_DATABASE", "database/database.sqlite")
    if not os.path.isabs(path):
        # Resolve relative to project root
        root_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
        path = os.path.join(root_dir, path)
    return path


def get_pool() -> pool.ThreadedConnectionPool:
    """Get or create the PostgreSQL database connection pool."""
    global _connection_pool

    if _connection_pool is None or _connection_pool.closed:
        _connection_pool = pool.ThreadedConnectionPool(
            minconn=2,
            maxconn=10,
            host=os.getenv("DB_HOST", "postgres"),
            port=int(os.getenv("DB_PORT", "5432")),
            dbname=os.getenv("DB_DATABASE", "blackdoor"),
            user=os.getenv("DB_USERNAME", "blackdoor"),
            password=os.getenv("DB_PASSWORD", "secret"),
        )

    return _connection_pool


def get_sqlite_connection() -> sqlite3.Connection:
    """Get or create a single SQLite connection."""
    global _sqlite_conn
    if _sqlite_conn is None:
        db_path = get_sqlite_path()
        _sqlite_conn = sqlite3.connect(db_path, check_same_thread=False)
        _sqlite_conn.row_factory = sqlite3.Row
    return _sqlite_conn


@contextmanager
def get_connection():
    """Context manager that yields a database connection."""
    if is_sqlite():
        yield get_sqlite_connection()
    else:
        conn = None
        try:
            conn = get_pool().getconn()
            yield conn
        finally:
            if conn is not None:
                get_pool().putconn(conn)


def rewrite_query(query: str) -> str:
    """Rewrite PostgreSQL queries to be SQLite-compatible."""
    if not is_sqlite():
        return query

    # 1. Replace generate_series CTE
    query = query.replace(
        "generate_series(%s::date, %s::date, '1 day'::interval)::date AS day",
        "? AS day UNION ALL SELECT date(day, '+1 day') FROM date_series WHERE day < ?"
    )
    query = query.replace(
        "generate_series(%s::date, %s::date, '1 day'::interval)::date",
        "? AS day UNION ALL SELECT date(day, '+1 day') FROM date_series WHERE day < ?"
    )
    if "date_series" in query:
        query = query.replace("WITH date_series", "WITH RECURSIVE date_series")

    # 2. Replace type casting ::text and ::date
    query = query.replace("::text", "")
    query = query.replace("d.created_at::date", "date(d.created_at)")
    query = query.replace("::date", "")

    # 3. Replace placeholders %s with ?
    query = query.replace("%s", "?")

    return query


@contextmanager
def get_cursor(commit: bool = False):
    """Context manager that yields a database cursor."""
    with get_connection() as conn:
        cursor = conn.cursor()
        try:
            yield cursor
            if commit:
                conn.commit()
        except Exception:
            conn.rollback()
            raise
        finally:
            cursor.close()


def fetch_all(query: str, params: tuple = ()) -> list[dict]:
    """Execute a query and return all rows as a list of dicts."""
    query = rewrite_query(query)
    if is_sqlite() and "RECURSIVE date_series" in query:
        num_placeholders = query.count("?")
        if len(params) == 2 and num_placeholders == 4:
            params = (params[0], params[1], params[0], params[1])
        elif len(params) == 4 and num_placeholders == 2:
            params = (params[0], params[1])

    with get_cursor() as cur:
        cur.execute(query, params)
        if is_sqlite():
            return [dict(row) for row in cur.fetchall()]
        else:
            columns = [desc[0] for desc in cur.description]
            return [dict(zip(columns, row)) for row in cur.fetchall()]


def fetch_one(query: str, params: tuple = ()) -> dict | None:
    """Execute a query and return the first row as a dict, or None."""
    query = rewrite_query(query)
    with get_cursor() as cur:
        cur.execute(query, params)
        row = cur.fetchone()
        if row is None:
            return None
        if is_sqlite():
            return dict(row)
        else:
            columns = [desc[0] for desc in cur.description]
            return dict(zip(columns, row))


def check_connection() -> bool:
    """Check if the database connection is alive."""
    try:
        with get_cursor() as cur:
            cur.execute("SELECT 1")
            return True
    except Exception:
        return False


def close_pool():
    """Close all connections in the pool / SQLite connection."""
    global _connection_pool, _sqlite_conn
    if _connection_pool is not None and not _connection_pool.closed:
        _connection_pool.closeall()
        _connection_pool = None
    if _sqlite_conn is not None:
        _sqlite_conn.close()
        _sqlite_conn = None
