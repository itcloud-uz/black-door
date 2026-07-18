"""
Black Door — FastAPI Report Microservice
Generates financial reports (income/expense, cash balances, debts, categories)
in JSON, Excel, and PDF formats.

All monetary values are stored and transmitted as integers (cents for USD, tiyin for UZS).
"""

import os
from datetime import date, datetime
from typing import Optional
from contextlib import asynccontextmanager

from fastapi import FastAPI, HTTPException, Query, Path
from fastapi.responses import FileResponse, JSONResponse
from pydantic import BaseModel, Field

from db import check_connection, fetch_all, close_pool
from generators.excel_generator import generate_income_expense_excel, generate_generic_excel
from generators.pdf_generator import generate_income_expense_pdf, generate_generic_pdf


# ─── Constants ────────────────────────────────────────────────────────────────
REPORT_FILES_DIR = os.getenv("REPORT_FILES_DIR", "/app/generated_reports")


# ─── Lifespan ─────────────────────────────────────────────────────────────────
@asynccontextmanager
async def lifespan(app: FastAPI):
    """Startup and shutdown events."""
    os.makedirs(REPORT_FILES_DIR, exist_ok=True)
    yield
    close_pool()


# ─── App ──────────────────────────────────────────────────────────────────────
app = FastAPI(
    title="Black Door Report Service",
    description="Financial report generation microservice for Black Door ERP",
    version="1.0.0",
    lifespan=lifespan,
)


# ═════════════════════════════════════════════════════════════════════════════
# REQUEST / RESPONSE MODELS
# ═════════════════════════════════════════════════════════════════════════════

class IncomeExpenseRequest(BaseModel):
    start_date: date
    end_date: date
    cash_account_id: Optional[int] = None
    category_id: Optional[int] = None


class IncomeExpenseItem(BaseModel):
    date: str
    category: str
    amount_usd: int
    amount_uzs: int
    type: str
    note: Optional[str] = None


class IncomeExpenseResponse(BaseModel):
    period: str
    total_income_usd: int
    total_income_uzs: int
    total_expense_usd: int
    total_expense_uzs: int
    items: list[IncomeExpenseItem]


class CashBalancesRequest(BaseModel):
    start_date: date
    end_date: date


class DebtRegistryRequest(BaseModel):
    as_of_date: Optional[date] = None


class CategoryBreakdownRequest(BaseModel):
    start_date: date
    end_date: date
    type: str = Field(..., pattern="^(income|expense)$")


class ObjectDailyRequest(BaseModel):
    object_id: int
    date: date


class ExportRequest(BaseModel):
    """Generic export request — wraps any report data."""
    report_type: str = Field(..., pattern="^(income_expense|cash_balances|debt_registry|category_breakdown|object_daily)$")
    data: dict


# ═════════════════════════════════════════════════════════════════════════════
# HEALTH CHECK
# ═════════════════════════════════════════════════════════════════════════════

@app.get("/health")
async def health():
    """Health check endpoint."""
    db_ok = check_connection()
    return {
        "status": "healthy" if db_ok else "degraded",
        "service": "reports",
        "database": "connected" if db_ok else "disconnected",
        "timestamp": datetime.now().isoformat(),
    }


# ═════════════════════════════════════════════════════════════════════════════
# 1. INCOME / EXPENSE REPORT
# ═════════════════════════════════════════════════════════════════════════════

@app.post("/reports/income-expense", response_model=IncomeExpenseResponse)
async def income_expense_report(req: IncomeExpenseRequest):
    """
    Generate income/expense report for the given date range.
    Optionally filter by cash account or category.
    """
    conditions = ["t.transaction_date >= %s", "t.transaction_date <= %s"]
    params: list = [req.start_date, req.end_date]

    if req.cash_account_id is not None:
        conditions.append("t.cash_account_id = %s")
        params.append(req.cash_account_id)

    if req.category_id is not None:
        conditions.append("t.category_id = %s")
        params.append(req.category_id)

    where_clause = " AND ".join(conditions)

    # ── Fetch items ──────────────────────────────────────────────────────
    query = f"""
        SELECT
            t.transaction_date::text   AS date,
            COALESCE(c.name, 'N/A')    AS category,
            CASE WHEN t.currency = 'USD' THEN t.amount ELSE 0 END AS amount_usd,
            CASE WHEN t.currency = 'UZS' THEN t.amount ELSE 0 END AS amount_uzs,
            t.type,
            t.note
        FROM transactions t
        LEFT JOIN transaction_categories c ON c.id = t.category_id
        WHERE {where_clause}
        ORDER BY t.transaction_date DESC, t.id DESC
    """
    rows = fetch_all(query, tuple(params))

    # ── Calculate totals ─────────────────────────────────────────────────
    totals_query = f"""
        SELECT
            COALESCE(SUM(CASE WHEN t.type = 'income'  AND t.currency = 'USD' THEN t.amount ELSE 0 END), 0) AS total_income_usd,
            COALESCE(SUM(CASE WHEN t.type = 'income'  AND t.currency = 'UZS' THEN t.amount ELSE 0 END), 0) AS total_income_uzs,
            COALESCE(SUM(CASE WHEN t.type = 'expense' AND t.currency = 'USD' THEN t.amount ELSE 0 END), 0) AS total_expense_usd,
            COALESCE(SUM(CASE WHEN t.type = 'expense' AND t.currency = 'UZS' THEN t.amount ELSE 0 END), 0) AS total_expense_uzs
        FROM transactions t
        WHERE {where_clause}
    """
    totals = fetch_all(totals_query, tuple(params))
    t = totals[0] if totals else {}

    return IncomeExpenseResponse(
        period=f"{req.start_date} — {req.end_date}",
        total_income_usd=t.get("total_income_usd", 0),
        total_income_uzs=t.get("total_income_uzs", 0),
        total_expense_usd=t.get("total_expense_usd", 0),
        total_expense_uzs=t.get("total_expense_uzs", 0),
        items=[
            IncomeExpenseItem(
                date=row["date"],
                category=row["category"],
                amount_usd=row["amount_usd"] or 0,
                amount_uzs=row["amount_uzs"] or 0,
                type=row["type"],
                note=row.get("note"),
            )
            for row in rows
        ],
    )


# ═════════════════════════════════════════════════════════════════════════════
# 2. CASH BALANCES
# ═════════════════════════════════════════════════════════════════════════════

@app.post("/reports/cash-balances")
async def cash_balances_report(req: CashBalancesRequest):
    """
    Daily cash balance snapshots for each cash account over the given period.
    """
    query = """
        WITH date_series AS (
            SELECT generate_series(%s::date, %s::date, '1 day'::interval)::date AS day
        ),
        daily_reconstruction AS (
            SELECT
                cb.cash_account_id       AS account_id,
                ca.name                  AS account_name,
                cb.currency,
                ds.day,
                cb.balance - COALESCE(SUM(
                    CASE WHEN t.transaction_date > ds.day THEN
                        CASE
                            WHEN t.type IN ('income', 'transfer_in') THEN t.amount
                            WHEN t.type IN ('expense', 'transfer_out') THEN -t.amount
                            ELSE 0
                        END
                    ELSE 0 END
                ), 0) AS balance
            FROM cash_balances cb
            JOIN cash_accounts ca ON ca.id = cb.cash_account_id
            CROSS JOIN date_series ds
            LEFT JOIN transactions t ON t.cash_account_id = cb.cash_account_id 
                                    AND t.currency = cb.currency
            GROUP BY cb.cash_account_id, ca.name, cb.currency, ds.day, cb.balance
        )
        SELECT
            account_id,
            account_name,
            currency,
            day::text AS date,
            balance
        FROM daily_reconstruction
        ORDER BY account_name, currency, day
    """
    rows = fetch_all(query, (req.start_date, req.end_date))

    # ── Group by account ─────────────────────────────────────────────────
    accounts: dict = {}
    for row in rows:
        aid = row["account_id"]
        if aid not in accounts:
            accounts[aid] = {
                "account_id": aid,
                "account_name": row["account_name"],
                "currency": row["currency"],
                "daily_balances": [],
            }
        accounts[aid]["daily_balances"].append({
            "date": row["date"],
            "balance": row["balance"],
        })

    return {
        "period": f"{req.start_date} — {req.end_date}",
        "accounts": list(accounts.values()),
    }


# ═════════════════════════════════════════════════════════════════════════════
# 3. DEBT REGISTRY
# ═════════════════════════════════════════════════════════════════════════════

@app.post("/reports/debt-registry")
async def debt_registry_report(req: DebtRegistryRequest):
    """
    All counterparties with their outstanding debt balances in USD and UZS.
    """
    as_of = req.as_of_date or date.today()

    query = """
        SELECT
            cp.id                      AS counterparty_id,
            cp.name                    AS counterparty_name,
            cp.category                AS type,
            COALESCE(SUM(CASE WHEN t.currency = 'USD' AND t.type IN ('income', 'transfer_in') THEN t.amount ELSE 0 END), 0) AS total_usd,
            COALESCE(SUM(CASE WHEN t.currency = 'UZS' AND t.type IN ('income', 'transfer_in') THEN t.amount ELSE 0 END), 0) AS total_uzs,
            COALESCE(SUM(CASE WHEN t.currency = 'USD' AND t.type IN ('expense', 'transfer_out') THEN t.amount ELSE 0 END), 0) AS paid_usd,
            COALESCE(SUM(CASE WHEN t.currency = 'UZS' AND t.type IN ('expense', 'transfer_out') THEN t.amount ELSE 0 END), 0) AS paid_uzs,
            COALESCE(SUM(CASE WHEN t.currency = 'USD' THEN (CASE WHEN t.type IN ('income', 'transfer_in') THEN t.amount ELSE -t.amount END) ELSE 0 END), 0) AS outstanding_usd,
            COALESCE(SUM(CASE WHEN t.currency = 'UZS' THEN (CASE WHEN t.type IN ('income', 'transfer_in') THEN t.amount ELSE -t.amount END) ELSE 0 END), 0) AS outstanding_uzs
        FROM counterparties cp
        LEFT JOIN transactions t ON t.counterparty_id = cp.id AND t.transaction_date <= %s
        GROUP BY cp.id, cp.name, cp.category
        HAVING outstanding_usd != 0 OR outstanding_uzs != 0
        ORDER BY cp.name
    """
    rows = fetch_all(query, (as_of,))

    return {
        "as_of_date": str(as_of),
        "entries": [
            {
                "counterparty_id": row["counterparty_id"],
                "counterparty_name": row["counterparty_name"],
                "type": row["type"],
                "total_usd": row["total_usd"],
                "total_uzs": row["total_uzs"],
                "paid_usd": row["paid_usd"],
                "paid_uzs": row["paid_uzs"],
                "outstanding_usd": row["outstanding_usd"],
                "outstanding_uzs": row["outstanding_uzs"],
            }
            for row in rows
        ],
    }


# ═════════════════════════════════════════════════════════════════════════════
# 4. CATEGORY BREAKDOWN
# ═════════════════════════════════════════════════════════════════════════════

@app.post("/reports/category-breakdown")
async def category_breakdown_report(req: CategoryBreakdownRequest):
    """
    Amounts grouped by category for a given type (income/expense).
    """
    query = """
        SELECT
            COALESCE(c.name, 'Boshqa')    AS category,
            COALESCE(SUM(CASE WHEN t.currency = 'USD' THEN t.amount ELSE 0 END), 0) AS total_usd,
            COALESCE(SUM(CASE WHEN t.currency = 'UZS' THEN t.amount ELSE 0 END), 0) AS total_uzs,
            COUNT(t.id)                     AS transaction_count
        FROM transactions t
        LEFT JOIN transaction_categories c ON c.id = t.category_id
        WHERE t.type = %s
          AND t.transaction_date >= %s
          AND t.transaction_date <= %s
        GROUP BY c.name
        ORDER BY COALESCE(SUM(CASE WHEN t.currency = 'USD' THEN t.amount ELSE 0 END), 0) DESC
    """
    rows = fetch_all(query, (req.type, req.start_date, req.end_date))

    grand_total_usd = sum(r["total_usd"] for r in rows)
    grand_total_uzs = sum(r["total_uzs"] for r in rows)

    return {
        "period": f"{req.start_date} — {req.end_date}",
        "type": req.type,
        "grand_total_usd": grand_total_usd,
        "grand_total_uzs": grand_total_uzs,
        "categories": [
            {
                "category": row["category"],
                "total_usd": row["total_usd"],
                "total_uzs": row["total_uzs"],
                "transaction_count": row["transaction_count"],
                "percentage_usd": round(row["total_usd"] / grand_total_usd * 100, 2) if grand_total_usd else 0,
            }
            for row in rows
        ],
    }


# ═════════════════════════════════════════════════════════════════════════════
# 5. OBJECT DAILY SUMMARY
# ═════════════════════════════════════════════════════════════════════════════

@app.post("/reports/object-daily")
async def object_daily_report(req: ObjectDailyRequest):
    """
    Daily summary for a specific object (construction site, project, etc.).
    """
    # ── Object info ──────────────────────────────────────────────────────
    obj_query = """
        SELECT id, name FROM objects WHERE id = %s
    """
    obj_rows = fetch_all(obj_query, (req.object_id,))
    if not obj_rows:
        raise HTTPException(status_code=404, detail=f"Object with id={req.object_id} not found")

    obj = obj_rows[0]

    # ── Transactions for the day ─────────────────────────────────────────
    txn_query = """
        SELECT
            t.transaction_date::text   AS date,
            COALESCE(c.name, 'N/A')    AS category,
            CASE WHEN t.currency = 'USD' THEN t.amount ELSE 0 END AS amount_usd,
            CASE WHEN t.currency = 'UZS' THEN t.amount ELSE 0 END AS amount_uzs,
            t.type,
            t.note
        FROM transactions t
        LEFT JOIN transaction_categories c ON c.id = t.category_id
        WHERE t.object_id = %s
          AND t.transaction_date = %s
        ORDER BY t.id
    """
    txn_rows = fetch_all(txn_query, (req.object_id, req.date))

    total_income_usd = sum(r["amount_usd"] or 0 for r in txn_rows if r["type"] == "income")
    total_income_uzs = sum(r["amount_uzs"] or 0 for r in txn_rows if r["type"] == "income")
    total_expense_usd = sum(r["amount_usd"] or 0 for r in txn_rows if r["type"] == "expense")
    total_expense_uzs = sum(r["amount_uzs"] or 0 for r in txn_rows if r["type"] == "expense")

    return {
        "object_id": obj["id"],
        "object_name": obj["name"],
        "date": str(req.date),
        "total_income_usd": total_income_usd,
        "total_income_uzs": total_income_uzs,
        "total_expense_usd": total_expense_usd,
        "total_expense_uzs": total_expense_uzs,
        "transactions": [
            {
                "date": row["date"],
                "category": row["category"],
                "amount_usd": row["amount_usd"] or 0,
                "amount_uzs": row["amount_uzs"] or 0,
                "type": row["type"],
                "note": row.get("note"),
            }
            for row in txn_rows
        ],
    }


# ═════════════════════════════════════════════════════════════════════════════
# 6. EXPORT (Excel / PDF)
# ═════════════════════════════════════════════════════════════════════════════

@app.post("/reports/export/{fmt}")
async def export_report(fmt: str = Path(..., pattern="^(excel|pdf)$"), body: ExportRequest = ...):
    """
    Generate an Excel or PDF file from the given report data.
    Returns the download URL.
    """
    report_type = body.report_type
    data = body.data

    try:
        if report_type == "income_expense":
            if fmt == "excel":
                filename = generate_income_expense_excel(data)
            else:
                filename = generate_income_expense_pdf(data)

        elif report_type == "category_breakdown":
            headers = ["Kategoriya", "Jami (USD)", "Jami (UZS)", "Tranzaksiyalar"]
            rows = [
                [
                    cat["category"],
                    cat["total_usd"],
                    cat["total_uzs"],
                    cat["transaction_count"],
                ]
                for cat in data.get("categories", [])
            ]
            title = f"Kategoriya bo'yicha — {data.get('type', '')} ({data.get('period', '')})"

            if fmt == "excel":
                filename = generate_generic_excel(
                    title=title,
                    headers=headers,
                    rows=rows,
                    usd_columns=[1],
                    uzs_columns=[2],
                    sheet_name="Kategoriyalar",
                )
            else:
                filename = generate_generic_pdf(
                    title=title,
                    headers=headers,
                    rows=rows,
                    usd_columns=[1],
                    uzs_columns=[2],
                )

        elif report_type == "debt_registry":
            headers = ["Kontragent", "Turi", "Jami (USD)", "Jami (UZS)", "To'langan (USD)", "To'langan (UZS)", "Qoldiq (USD)", "Qoldiq (UZS)"]
            rows = [
                [
                    entry["counterparty_name"],
                    entry["type"],
                    entry["total_usd"],
                    entry["total_uzs"],
                    entry["paid_usd"],
                    entry["paid_uzs"],
                    entry["outstanding_usd"],
                    entry["outstanding_uzs"],
                ]
                for entry in data.get("entries", [])
            ]
            title = f"Qarz reestri — {data.get('as_of_date', '')}"

            if fmt == "excel":
                filename = generate_generic_excel(
                    title=title,
                    headers=headers,
                    rows=rows,
                    usd_columns=[2, 4, 6],
                    uzs_columns=[3, 5, 7],
                    sheet_name="Qarz reestri",
                )
            else:
                filename = generate_generic_pdf(
                    title=title,
                    headers=headers,
                    rows=rows,
                    usd_columns=[2, 4, 6],
                    uzs_columns=[3, 5, 7],
                )

        elif report_type == "cash_balances":
            headers = ["Hisob", "Valyuta", "Sana", "Balans"]
            rows = []
            for account in data.get("accounts", []):
                currency = account.get("currency", "USD")
                for bal in account.get("daily_balances", []):
                    rows.append([
                        account["account_name"],
                        currency,
                        bal["date"],
                        bal["balance"],
                    ])
            title = f"Kassa qoldiqlari — {data.get('period', '')}"
            usd_cols = [3] if any(a.get("currency") == "USD" for a in data.get("accounts", [])) else []

            if fmt == "excel":
                filename = generate_generic_excel(
                    title=title,
                    headers=headers,
                    rows=rows,
                    usd_columns=usd_cols,
                    sheet_name="Kassa qoldiqlari",
                )
            else:
                filename = generate_generic_pdf(
                    title=title,
                    headers=headers,
                    rows=rows,
                    usd_columns=usd_cols,
                )

        elif report_type == "object_daily":
            headers = ["Sana", "Kategoriya", "Turi", "Summa (USD)", "Summa (UZS)", "Izoh"]
            rows = [
                [
                    txn["date"],
                    txn["category"],
                    txn["type"],
                    txn["amount_usd"],
                    txn["amount_uzs"],
                    txn.get("note", ""),
                ]
                for txn in data.get("transactions", [])
            ]
            obj_name = data.get("object_name", "")
            title = f"Kunlik hisobot — {obj_name} ({data.get('date', '')})"

            if fmt == "excel":
                filename = generate_income_expense_excel(data)
            else:
                filename = generate_income_expense_pdf(data)

        else:
            raise HTTPException(status_code=400, detail=f"Unknown report type: {report_type}")

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Report generation failed: {str(e)}")

    return {
        "filename": filename,
        "download_url": f"/reports/download/{filename}",
        "format": fmt,
    }


# ═════════════════════════════════════════════════════════════════════════════
# 7. DOWNLOAD
# ═════════════════════════════════════════════════════════════════════════════

@app.get("/reports/download/{filename}")
async def download_report(filename: str):
    """Serve a generated report file for download."""
    filepath = os.path.join(REPORT_FILES_DIR, filename)

    if not os.path.isfile(filepath):
        raise HTTPException(status_code=404, detail="Report file not found")

    # ── Determine media type ─────────────────────────────────────────────
    if filename.endswith(".xlsx"):
        media_type = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
    elif filename.endswith(".pdf"):
        media_type = "application/pdf"
    else:
        media_type = "application/octet-stream"

    return FileResponse(
        path=filepath,
        filename=filename,
        media_type=media_type,
    )
