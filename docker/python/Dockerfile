FROM python:3.12-slim

# ─── System Dependencies ─────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y \
    libpq-dev \
    gcc \
    curl \
    && rm -rf /var/lib/apt/lists/*

# ─── Working Directory ───────────────────────────────────────────────────────
WORKDIR /app

# ─── Python Dependencies ─────────────────────────────────────────────────────
COPY services/reports/requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# ─── Application Code ────────────────────────────────────────────────────────
COPY services/reports/ .

# ─── Create Reports Directory ────────────────────────────────────────────────
RUN mkdir -p /app/generated_reports

# ─── Non-root User ───────────────────────────────────────────────────────────
RUN useradd --create-home appuser \
    && chown -R appuser:appuser /app
USER appuser

EXPOSE 8001

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001", "--workers", "2"]
