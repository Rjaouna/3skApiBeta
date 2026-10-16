from pathlib import Path
import json
import re

from PIL import Image, ImageDraw
from pypdf import PdfReader
from history_data import ROWS

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[1]
pdf = ROOT / 'output/pdf/3SK_recapitulatif_modifications_janvier_octobre_2026.pdf'
reader = PdfReader(pdf)
texts = [page.extract_text() for page in reader.pages]
all_text = '\n'.join(texts)
assert '\ufffd' not in all_text, 'Replacement glyph in PDF text'
assert '\u25a0' not in all_text, 'Black square in PDF text'
assert all(title in all_text.replace('\n', ' ') for _, _, _, title, *_ in ROWS), 'Missing title'
ids = re.findall(r'(?m)^([0-9]{3})$', '\n'.join(texts[2:13]))
assert ids == [f'{i:03d}' for i in range(1, len(ROWS) + 1)], ids
assert '190,5-336 h' in all_text
assert len(reader.pages) == 17
for i, text in enumerate(texts, 1):
    assert f'{i} / {len(reader.pages)}' in text

pngs = sorted(HERE.glob('page-*.png'))
assert len(pngs) == len(reader.pages)
for start in range(0, len(pngs), 6):
    group = pngs[start:start + 6]
    thumb_w, thumb_h = 360, 510
    sheet = Image.new('RGB', (3 * 384, 2 * 552), '#e4e8ee')
    draw = ImageDraw.Draw(sheet)
    for index, path in enumerate(group):
        img = Image.open(path).convert('RGB')
        img.thumbnail((thumb_w, thumb_h))
        x = (index % 3) * 384 + 12
        y = (index // 3) * 552 + 28
        sheet.paste(img, (x, y))
        draw.text((x, y - 19), f'Page {start + index + 1}', fill='#174ca4')
    sheet.save(HERE / f'contact-{start // 6 + 1}.png')

print(json.dumps({'pages': len(reader.pages), 'rows_verified': len(ids), 'totals': '190.5-336 hours', 'glyph_checks': 'OK', 'contact_sheets': 3}))
