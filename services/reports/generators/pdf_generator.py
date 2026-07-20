"""
Black Door — PDF Report Generator
Uses reportlab to create professional PDF reports with branding.
All monetary values are stored as integers (cents/tiyin) and displayed divided by 100.
"""

import os
import time
import boto3
from botocore.client import Config
from datetime import datetime
from reportlab.lib import colors
from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import mm, cm
from reportlab.platypus import (
    SimpleDocTemplate,
    Table,
    TableStyle,
    Paragraph,
    Spacer,
    PageBreak,
    Image,
)
from reportlab.lib.enums import TA_CENTER, TA_RIGHT, TA_LEFT


# ─── Color Constants ─────────────────────────────────────────────────────────
BRAND_DARK = colors.HexColor("#1F4E79")
BRAND_LIGHT = colors.HexColor("#D6E4F0")
BRAND_ACCENT = colors.HexColor("#2E75B6")
HEADER_TEXT = colors.white
ROW_ALT = colors.HexColor("#F2F7FB")


def _cents_to_display(value: int | None) -> str:
    """Convert integer cents/tiyin to formatted display string."""
    if value is None:
        return "0.00"
    amount = value / 100.0
    return f"{amount:,.2f}"


_logo_cache_path = None
_logo_last_fetched = 0
CACHE_DURATION_SECS = 60

def _download_logo_from_s3() -> str | None:
    global _logo_cache_path, _logo_last_fetched
    now = time.time()
    
    if _logo_cache_path and os.path.exists(_logo_cache_path) and (now - _logo_last_fetched < CACHE_DURATION_SECS):
        return _logo_cache_path
        
    try:
        endpoint_url = os.getenv("AWS_ENDPOINT")
        access_key = os.getenv("AWS_ACCESS_KEY_ID")
        secret_key = os.getenv("AWS_SECRET_ACCESS_KEY")
        bucket = os.getenv("AWS_BUCKET", "blackdoor")
        
        if not access_key or not secret_key:
            return None
            
        s3 = boto3.client(
            "s3",
            aws_access_key_id=access_key,
            aws_secret_access_key=secret_key,
            region_name=os.getenv("AWS_DEFAULT_REGION", "us-east-1"),
            endpoint_url=endpoint_url,
            config=Config(signature_version="s3v4")
        )
        
        temp_path = "/tmp/logo_vertical.png"
        os.makedirs(os.path.dirname(temp_path), exist_ok=True)
        
        try:
            s3.download_file(bucket, "branding/custom_logo_vertical.png", temp_path)
        except Exception:
            try:
                s3.download_file(bucket, "branding/logo_vertical.png", temp_path)
            except Exception:
                return None
                
        _logo_cache_path = temp_path
        _logo_last_fetched = now
        return _logo_cache_path
    except Exception as e:
        print(f"Error downloading logo from S3: {e}")
        return None


def _get_logo_flowable(width_mm: float = 30.0, height_mm: float = 30.0) -> Image | None:
    """Load the custom vertical logo from S3/MinIO with a local cache fallback."""
    s3_path = _download_logo_from_s3()
    if s3_path and os.path.exists(s3_path):
        try:
            return Image(s3_path, width=width_mm * mm, height=height_mm * mm)
        except Exception as e:
            print(f"Error loading S3 logo: {e}")

    # Fallback to local paths
    for path in ["/app/branding/custom_logo_vertical.png", "/app/branding/logo_vertical.png"]:
        if os.path.exists(path):
            try:
                return Image(path, width=width_mm * mm, height=height_mm * mm)
            except Exception as e:
                print(f"Error loading local fallback logo: {e}")
    return None


def _build_styles():
    """Create custom paragraph styles."""
    styles = getSampleStyleSheet()

    styles.add(ParagraphStyle(
        name="BrandTitle",
        parent=styles["Title"],
        fontSize=18,
        textColor=BRAND_DARK,
        alignment=TA_CENTER,
        spaceAfter=6 * mm,
    ))

    styles.add(ParagraphStyle(
        name="BrandSubtitle",
        parent=styles["Normal"],
        fontSize=11,
        textColor=colors.gray,
        alignment=TA_CENTER,
        spaceAfter=4 * mm,
        fontName="Helvetica-Oblique",
    ))

    styles.add(ParagraphStyle(
        name="SummaryLabel",
        parent=styles["Normal"],
        fontSize=10,
        textColor=BRAND_DARK,
        fontName="Helvetica-Bold",
    ))

    styles.add(ParagraphStyle(
        name="SummaryValue",
        parent=styles["Normal"],
        fontSize=10,
        alignment=TA_RIGHT,
        fontName="Helvetica-Bold",
    ))

    styles.add(ParagraphStyle(
        name="FooterStyle",
        parent=styles["Normal"],
        fontSize=8,
        textColor=colors.gray,
        alignment=TA_CENTER,
    ))

    return styles


def _header_footer(canvas, doc):
    """Draw page header and footer on each page."""
    canvas.saveState()

    # ── Header Line ──────────────────────────────────────────────────────
    page_width = doc.pagesize[0]
    canvas.setStrokeColor(BRAND_DARK)
    canvas.setLineWidth(2)
    canvas.line(15 * mm, doc.pagesize[1] - 12 * mm, page_width - 15 * mm, doc.pagesize[1] - 12 * mm)

    canvas.setFont("Helvetica-Bold", 8)
    canvas.setFillColor(BRAND_DARK)
    canvas.drawString(15 * mm, doc.pagesize[1] - 10 * mm, "BLACK DOOR")

    # ── Footer ───────────────────────────────────────────────────────────
    canvas.setStrokeColor(colors.lightgrey)
    canvas.setLineWidth(0.5)
    canvas.line(15 * mm, 15 * mm, page_width - 15 * mm, 15 * mm)

    canvas.setFont("Helvetica", 7)
    canvas.setFillColor(colors.gray)
    canvas.drawString(15 * mm, 10 * mm, f"Yaratilgan: {datetime.now().strftime('%Y-%m-%d %H:%M')}")
    canvas.drawRightString(page_width - 15 * mm, 10 * mm, f"Sahifa {doc.page}")

    canvas.restoreState()


def _build_table(headers: list[str], rows: list[list[str]], col_widths: list[float] | None = None) -> Table:
    """Build a styled reportlab Table."""
    table_data = [headers] + rows

    table = Table(table_data, colWidths=col_widths, repeatRows=1)

    style_commands = [
        # ── Header ───────────────────────────────────────────────────
        ("BACKGROUND", (0, 0), (-1, 0), BRAND_DARK),
        ("TEXTCOLOR", (0, 0), (-1, 0), HEADER_TEXT),
        ("FONTNAME", (0, 0), (-1, 0), "Helvetica-Bold"),
        ("FONTSIZE", (0, 0), (-1, 0), 9),
        ("ALIGN", (0, 0), (-1, 0), "CENTER"),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("BOTTOMPADDING", (0, 0), (-1, 0), 8),
        ("TOPPADDING", (0, 0), (-1, 0), 8),

        # ── Data Rows ────────────────────────────────────────────────
        ("FONTNAME", (0, 1), (-1, -1), "Helvetica"),
        ("FONTSIZE", (0, 1), (-1, -1), 8),
        ("BOTTOMPADDING", (0, 1), (-1, -1), 5),
        ("TOPPADDING", (0, 1), (-1, -1), 5),

        # ── Grid ─────────────────────────────────────────────────────
        ("GRID", (0, 0), (-1, -1), 0.5, colors.lightgrey),
        ("LINEBELOW", (0, 0), (-1, 0), 1.5, BRAND_DARK),
    ]

    # ── Alternating Row Colors ───────────────────────────────────────
    for i in range(1, len(table_data)):
        if i % 2 == 0:
            style_commands.append(("BACKGROUND", (0, i), (-1, i), ROW_ALT))

    table.setStyle(TableStyle(style_commands))
    return table


def generate_income_expense_pdf(report_data: dict) -> str:
    """Generate income/expense report as PDF."""
    filename = f"income_expense_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pdf"
    filepath = os.path.join(os.getenv("REPORT_FILES_DIR", "/app/generated_reports"), filename)

    doc = SimpleDocTemplate(
        filepath,
        pagesize=landscape(A4),
        topMargin=20 * mm,
        bottomMargin=20 * mm,
        leftMargin=15 * mm,
        rightMargin=15 * mm,
    )

    styles = _build_styles()
    elements = []

    # ─── Logo ────────────────────────────────────────────────────────────
    logo = _get_logo_flowable(35.0, 35.0)
    if logo:
        elements.append(logo)
        elements.append(Spacer(1, 4 * mm))

    # ─── Title ───────────────────────────────────────────────────────────
    elements.append(Paragraph("Daromad va Xarajat Hisoboti", styles["BrandTitle"]))
    elements.append(Paragraph(f"Davr: {report_data.get('period', '')}", styles["BrandSubtitle"]))
    elements.append(Spacer(1, 4 * mm))

    # ─── Summary Table ───────────────────────────────────────────────────
    summary_data = [
        ["", "USD", "UZS"],
        [
            "Jami Daromad",
            _cents_to_display(report_data.get("total_income_usd", 0)),
            _cents_to_display(report_data.get("total_income_uzs", 0)),
        ],
        [
            "Jami Xarajat",
            _cents_to_display(report_data.get("total_expense_usd", 0)),
            _cents_to_display(report_data.get("total_expense_uzs", 0)),
        ],
    ]

    summary_table = Table(summary_data, colWidths=[60 * mm, 50 * mm, 60 * mm])
    summary_table.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), BRAND_LIGHT),
        ("FONTNAME", (0, 0), (-1, 0), "Helvetica-Bold"),
        ("FONTNAME", (0, 1), (0, -1), "Helvetica-Bold"),
        ("TEXTCOLOR", (0, 0), (0, -1), BRAND_DARK),
        ("ALIGN", (1, 0), (-1, -1), "RIGHT"),
        ("GRID", (0, 0), (-1, -1), 0.5, colors.lightgrey),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
        ("TOPPADDING", (0, 0), (-1, -1), 6),
    ]))
    elements.append(summary_table)
    elements.append(Spacer(1, 8 * mm))

    # ─── Detail Table ────────────────────────────────────────────────────
    headers = ["Sana", "Kategoriya", "Turi", "Summa (USD)", "Summa (UZS)", "Izoh"]
    rows = []
    for item in report_data.get("items", []):
        rows.append([
            str(item.get("date", "")),
            str(item.get("category", "")),
            str(item.get("type", "")),
            _cents_to_display(item.get("amount_usd")),
            _cents_to_display(item.get("amount_uzs")),
            str(item.get("note", "")),
        ])

    col_widths = [25 * mm, 40 * mm, 25 * mm, 35 * mm, 45 * mm, 60 * mm]
    detail_table = _build_table(headers, rows, col_widths)
    elements.append(detail_table)

    # ─── Build PDF ───────────────────────────────────────────────────────
    doc.build(elements, onFirstPage=_header_footer, onLaterPages=_header_footer)
    return filename


def generate_generic_pdf(
    title: str,
    headers: list[str],
    rows: list[list],
    usd_columns: list[int] | None = None,
    uzs_columns: list[int] | None = None,
    use_landscape: bool = True,
) -> str:
    """
    Generate a generic PDF report.
    usd_columns and uzs_columns are 0-indexed column indices that contain monetary values (int cents).
    """
    usd_columns = usd_columns or []
    uzs_columns = uzs_columns or []

    filename = f"report_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pdf"
    filepath = os.path.join(os.getenv("REPORT_FILES_DIR", "/app/generated_reports"), filename)

    page = landscape(A4) if use_landscape else A4

    doc = SimpleDocTemplate(
        filepath,
        pagesize=page,
        topMargin=20 * mm,
        bottomMargin=20 * mm,
        leftMargin=15 * mm,
        rightMargin=15 * mm,
    )

    styles = _build_styles()
    elements = []

    # ─── Logo ────────────────────────────────────────────────────────────
    logo = _get_logo_flowable(35.0, 35.0)
    if logo:
        elements.append(logo)
        elements.append(Spacer(1, 4 * mm))

    # ─── Title ───────────────────────────────────────────────────────────
    elements.append(Paragraph(title, styles["BrandTitle"]))
    elements.append(Spacer(1, 6 * mm))

    # ─── Format Rows ─────────────────────────────────────────────────────
    formatted_rows = []
    for row in rows:
        formatted_row = []
        for col_idx, value in enumerate(row):
            if col_idx in usd_columns or col_idx in uzs_columns:
                formatted_row.append(_cents_to_display(value))
            else:
                formatted_row.append(str(value) if value is not None else "")
        formatted_rows.append(formatted_row)

    table = _build_table(headers, formatted_rows)
    elements.append(table)

    doc.build(elements, onFirstPage=_header_footer, onLaterPages=_header_footer)
    return filename
