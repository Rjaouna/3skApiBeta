from collections import Counter
from pathlib import Path
from xml.sax.saxutils import escape
import json

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_RIGHT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas
from reportlab.platypus import (
    BaseDocTemplate, Frame, Image, KeepTogether, LongTable, PageBreak,
    PageTemplate, Paragraph, Spacer, Table, TableStyle,
)

from history_data import AUDITS, PERIODS, REVERSALS, ROWS, SOURCES

ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / 'output' / 'pdf'
OUT.mkdir(parents=True, exist_ok=True)
TARGET = OUT / '3SK_recapitulatif_modifications_janvier_octobre_2026.pdf'

FONT_DIR = Path('C:/Windows/Fonts')
for name, file in [('Arial', 'arial.ttf'), ('Arial-Bold', 'arialbd.ttf'), ('Arial-Italic', 'ariali.ttf')]:
    pdfmetrics.registerFont(TTFont(name, str(FONT_DIR / file)))
pdfmetrics.registerFontFamily('Arial', normal='Arial', bold='Arial-Bold', italic='Arial-Italic', boldItalic='Arial-Bold')

BLUE = colors.HexColor('#174CA4')
ORANGE = colors.HexColor('#F58216')
INK = colors.HexColor('#1E2936')
MUTED = colors.HexColor('#617080')
LINE = colors.HexColor('#DCE3EC')
PALE = colors.HexColor('#F3F6FA')
WHITE = colors.white
PAGE_W, PAGE_H = A4
W = PAGE_W - 80

STYLES = {
    'h1': ParagraphStyle('h1', fontName='Arial-Bold', fontSize=23, leading=28, textColor=BLUE, spaceAfter=12),
    'h2': ParagraphStyle('h2', fontName='Arial-Bold', fontSize=14, leading=18, textColor=BLUE, spaceBefore=16, spaceAfter=8),
    'body': ParagraphStyle('body', fontName='Arial', fontSize=10, leading=14.5, textColor=INK, spaceAfter=8),
    'small': ParagraphStyle('small', fontName='Arial', fontSize=8.5, leading=11.5, textColor=MUTED, spaceAfter=6),
    'cell': ParagraphStyle('cell', fontName='Arial', fontSize=9, leading=12, textColor=INK),
    'date': ParagraphStyle('date', fontName='Arial', fontSize=8.8, leading=12, textColor=INK),
    'effort': ParagraphStyle('effort', fontName='Arial-Bold', fontSize=9.5, leading=13, textColor=BLUE, alignment=TA_RIGHT),
    'th': ParagraphStyle('th', fontName='Arial-Bold', fontSize=8.6, leading=11, textColor=WHITE),
    'section': ParagraphStyle('section', fontName='Arial-Bold', fontSize=11, leading=15, textColor=BLUE),
    'metric': ParagraphStyle('metric', fontName='Arial-Bold', fontSize=21, leading=26, textColor=BLUE),
    'metric_label': ParagraphStyle('metric_label', fontName='Arial', fontSize=9, leading=12.5, textColor=MUTED),
}


def p(text, style='body'):
    return Paragraph(text, STYLES[style])


def n(value):
    return f'{value:g}'.replace('.', ',')


def hours(low, high):
    return f'{n(low)}-{n(high)} h'


def total(rows):
    return sum(r[5] for r in rows), sum(r[6] for r in rows)


def table_style(header=True):
    cmds = [
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('LEFTPADDING', (0, 0), (-1, -1), 9),
        ('RIGHTPADDING', (0, 0), (-1, -1), 9),
        ('TOPPADDING', (0, 0), (-1, -1), 9),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 9),
        ('LINEBELOW', (0, 0), (-1, -1), .4, LINE),
    ]
    if header:
        cmds += [('BACKGROUND', (0, 0), (-1, 0), BLUE), ('TOPPADDING', (0, 0), (-1, 0), 10), ('BOTTOMPADDING', (0, 0), (-1, 0), 10)]
    return TableStyle(cmds)


class NumberedCanvas(canvas.Canvas):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._states = []

    def showPage(self):
        self._states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        count = len(self._states)
        for state in self._states:
            self.__dict__.update(state)
            self.setStrokeColor(LINE)
            self.setLineWidth(.6)
            self.line(40, 37, PAGE_W - 40, 37)
            self.setFont('Arial', 8)
            self.setFillColor(MUTED)
            self.drawString(40, 24, '3SK Parts Auto | Récapitulatif au 09/10/2026')
            self.drawRightString(PAGE_W - 40, 24, f'{self._pageNumber} / {count}')
            super().showPage()
        super().save()


def page_header(c, doc):
    if doc.page == 1:
        return
    c.saveState()
    c.setFont('Arial-Bold', 8.5)
    c.setFillColor(BLUE)
    c.drawString(40, PAGE_H - 29, '3SK PARTS AUTO')
    c.setFont('Arial', 8)
    c.setFillColor(MUTED)
    c.drawRightString(PAGE_W - 40, PAGE_H - 29, 'HISTORIQUE DES INTERVENTIONS | 2026')
    c.setStrokeColor(LINE)
    c.setLineWidth(.6)
    c.line(40, PAGE_H - 37, PAGE_W - 40, PAGE_H - 37)
    c.restoreState()


doc = BaseDocTemplate(
    str(TARGET), pagesize=A4, leftMargin=40, rightMargin=40,
    topMargin=52, bottomMargin=49, title='3SK Parts Auto - Historique des modifications 2026',
    author='3SK Parts Auto', subject='Récapitulatif reconstitué des modifications et estimations de charge',
)
doc.addPageTemplates([PageTemplate(id='report', frames=[Frame(40, 49, W, PAGE_H - 101, leftPadding=0, rightPadding=0, topPadding=0, bottomPadding=0)], onPage=page_header)])

low, high = total(ROWS)
story = []
logo = ROOT / 'public/img/optimized/logo-3sk-partsauto-transparent.webp'
if logo.exists():
    img = Image(str(logo), width=225, height=225 * 456 / 1937)
    img.hAlign = 'LEFT'
    story += [Spacer(1, 18), img, Spacer(1, 50)]
else:
    story += [p('3SK PARTS AUTO', 'h1'), Spacer(1, 50)]

story += [
    p('HISTORIQUE DU PROJET', 'small'),
    p('Récapitulatif<br/>des modifications', 'h1'),
    p('Du 1er janvier au 9 octobre 2026', 'h2'),
    p('Site web et application Symfony 3SK Parts Auto', 'body'),
    Spacer(1, 22),
]

metrics = Table([
    [p(str(len(ROWS)), 'metric'), p(f'{n(low)}-{n(high)} h', 'metric')],
    [p('interventions techniques et visuelles identifiées', 'metric_label'), p('charge rétrospective estimée<br/>hors audits séparés et retours arrière', 'metric_label')],
], colWidths=[W * .48, W * .52])
metrics.setStyle(TableStyle([
    ('VALIGN', (0, 0), (-1, -1), 'TOP'), ('BACKGROUND', (0, 0), (-1, -1), PALE),
    ('LEFTPADDING', (0, 0), (-1, -1), 14), ('RIGHTPADDING', (0, 0), (-1, -1), 14),
    ('TOPPADDING', (0, 0), (-1, 0), 15), ('BOTTOMPADDING', (0, 1), (-1, 1), 16),
    ('LINEBEFORE', (0, 0), (0, -1), 3, ORANGE),
]))
story += [metrics, Spacer(1, 27), p('Un historique reconstitué, pas une feuille de temps', 'h2'),
    p('Les dates proviennent des échanges datés et des sauvegardes Git disponibles. Les durées sont des fourchettes indicatives de travail technique, pas des heures mesurées ni facturées.'),
    p('<b>Limite importante :</b> aucune trace accessible ne permet de détailler les travaux de janvier à mai. Les premiers échanges du projet remontent à juin ; le premier commit disponible est daté du 14 juin 2026.'),
    Spacer(1, 12), p('Document établi le 9 octobre 2026. Périmètre : projet local et conversation associée. La mise en production de chaque changement n’est pas systématiquement attestée.', 'small'),
    PageBreak(),
    p('Synthèse de la période', 'h1'),
    p('Les retouches successives d’un même sujet ont été regroupées lorsque cela évite de compter plusieurs fois le même travail. Une évolution ultérieure distincte reste identifiable à sa propre date.'),
]

summary = [[p('PÉRIODE', 'th'), p('LOTS', 'th'), p('CHARGE ESTIMÉE', 'th')]]
summary += [[p('Janvier à mai 2026', 'cell'), p('Non documenté', 'cell'), p('Non chiffrable', 'cell')]]
for key, name, sub in PERIODS:
    rr = [r for r in ROWS if r[0] == key]
    lo, hi = total(rr)
    summary.append([p(name + ('<br/><font size="8" color="#617080">Avant première trace du 14/06</font>' if key == 'early' else ''), 'cell'), p(str(len(rr)), 'cell'), p(hours(lo, hi), 'effort')])
summary.append([p('TOTAL PRINCIPAL', 'section'), p(str(len(ROWS)), 'section'), p(hours(low, high), 'effort')])
st = Table(summary, colWidths=[W * .50, W * .17, W * .33])
st.setStyle(table_style())
st.setStyle(TableStyle([('BACKGROUND', (0, -1), (-1, -1), PALE)]))
story += [st, p('Les lignes sans date retrouvée sont isolées du mois de juin : leur présence est vérifiée dans la première sauvegarde, mais leur jour réel d’exécution n’est pas établi.', 'small')]
story += [
    p('Comment lire les dates', 'h2'),
    p('<b>E - Échange daté :</b> date de la demande et de l’intervention retrouvée dans la conversation. Un commit peut être postérieur.'),
    p('<b>G - Git :</b> date du commit servant de repère. Pour les anciens échanges non horodatés, elle indique une sauvegarde de la modification, pas nécessairement le jour exact de travail.'),
    p('<b>R - Repère seulement :</b> date d’exécution non retrouvée. Première présence attestée au 14/06/2026 ; aucune date quotidienne n’est inventée.'),
    p('Ce qui est estimé', 'h2'),
    p('Charge indicative pour un développeur connaissant le projet : compréhension, réalisation et contrôles techniques ciblés. Les échanges de validation, l’hébergement, les attentes externes, l’exploitation récurrente et les déploiements non attestés ne sont pas chiffrés.'),
    p('Les fourchettes ne sont ni un devis contractuel ni un calcul à partir du temps d’exécution de l’assistant. Elles doivent être rapprochées de vos feuilles de temps avant tout usage de facturation.', 'small'),
    PageBreak(), p('Registre des modifications', 'h1'),
    p('Un numéro par intervention ou lot cohérent. La dernière colonne donne une estimation de charge. La référence en bas de chaque ligne permet de retrouver sa trace.', 'small'),
]

data = [[p('N° / DATE', 'th'), p('INTERVENTION ET PÉRIMÈTRE', 'th'), p('ESTIMATION', 'th')]]
commands = []
previous = None
period_labels = {key: (name, sub) for key, name, sub in PERIODS}
for idx, row in enumerate(ROWS, 1):
    period, date, kind, title, desc, lo, hi, source = row
    if period != previous:
        name, sub = period_labels[period]
        position = len(data)
        data.append([p(f'{escape(name)} <font name="Arial" size="9">| {escape(sub)}</font>', 'section'), '', ''])
        commands += [('SPAN', (0, position), (-1, position)), ('BACKGROUND', (0, position), (-1, position), PALE), ('NOSPLIT', (0, position), (-1, position + 1))]
        previous = period
    detail = p(f'<b>{escape(title)}</b><br/>{escape(desc)}<br/><font size="7.5" color="#617080">Trace : {escape(source)}</font>', 'cell')
    date_cell = f'<font color="#174CA4"><b>{idx:03d}</b></font><br/>{escape(date)}<br/><font size="7.5" color="#617080">{kind}</font>'
    if kind == 'R':
        date_cell += '<br/><font size="7.1" color="#617080">Trace : 14/06</font>'
    data.append([p(date_cell, 'date'), detail, p(hours(lo, hi), 'effort')])

ledger = LongTable(data, colWidths=[82, W - 157, 75], repeatRows=1, splitByRow=1, hAlign='LEFT')
ledger.setStyle(table_style())
ledger.setStyle(TableStyle(commands))
story += [ledger, Spacer(1, 12), p('Fin du registre principal. Les diagnostics sans correction attestée et les changements annulés sont distingués dans les pages suivantes.', 'small'), PageBreak()]

story += [p('Audits et accompagnement', 'h1'),
    p('Ces échanges ont aidé à décider ou à diagnostiquer, sans constituer systématiquement une modification livrée. Ils ne sont pas ajoutés au total principal.'),
]
audit_rows = [[p('DATE / REPÈRE', 'th'), p('DIAGNOSTIC OU CONSEIL', 'th'), p('ESTIMATION', 'th')]]
for date, title, desc, lo, hi in AUDITS:
    audit_rows.append([p(escape(date), 'date'), p(f'<b>{escape(title)}</b><br/>{escape(desc)}', 'cell'), p(hours(lo, hi) if lo is not None else 'Incluse au lot', 'effort')])
at = LongTable(audit_rows, colWidths=[94, W - 169, 75], repeatRows=1)
at.setStyle(table_style())
story += [at, Spacer(1, 12), p('Import et paniers : point de vigilance', 'h2'),
    p('L’alerte du 7 septembre n’équivaut pas à une correction. Le remplacement de la suppression des articles absents du CSV par une désactivation n’est pas attesté comme réalisé. Ce document ne promet donc pas une conservation des paniers dans tous les cas.'),
    PageBreak(), p('Retours arrière et limites', 'h1'),
    p('Les essais annulés ci-dessous ne sont pas présentés comme des fonctionnalités actives. Aucun temps supplémentaire de rollback n’est ajouté au total principal ; les retouches déjà regroupées dans un lot n’y sont pas comptées une seconde fois.'),
]
reversal_rows = [[p('DATE / REPÈRE', 'th'), p('CHANGEMENT ANNULÉ', 'th')]]
for date, title, desc in REVERSALS:
    reversal_rows.append([p(escape(date), 'date'), p(f'<b>{escape(title)}</b><br/>{escape(desc)}', 'cell')])
rt = Table(reversal_rows, colWidths=[115, W - 115])
rt.setStyle(table_style())
story += [rt, p('État local et travaux non attestés', 'h2'),
    p('<b>Hopic :</b> diagnostic daté du 22 septembre. La suppression du fallback est visible dans le header local au 9 octobre, mais sa date de réalisation n’est pas retrouvée ; estimation isolée de 0,25-0,5 h, hors total principal.'),
    p('<b>Stock :</b> le correctif du 6 octobre sur le détail article et l’API panier est local et non commité au moment de ce relevé. Aucun transfert serveur n’est supposé.'),
    p('<b>Logos des cartes :</b> intervention retrouvée dans les échanges et réalisée sur une branche dédiée. La correspondance ne couvre pas toutes les marques et ne concernait pas les cartes de derniers arrivages.'),
    p('<b>Migration import :</b> une migration accompagne la levée d’unicité sur Id ; son exécution sur le serveur n’est pas confirmée par l’historique consulté.'),
    p('<b>Tests :</b> les échanges mentionnent essentiellement lint PHP/Twig, conteneur Symfony, compilation et contrôles ponctuels. Cela ne vaut pas preuve d’une recette exhaustive navigateur, mobile, production ou de toute la logique métier.'),
    PageBreak(), p('Sources et traçabilité', 'h1'),
    p('Le relevé a été reconstruit depuis 230 tours de la conversation du projet et 61 commits présents sur la branche main entre le 14 juin et le 20 septembre 2026, puis complété par les différences locales au 9 octobre.'),
    p('Les intitulés des commits étant souvent peu descriptifs, les demandes utilisateur, les réponses de réalisation et les fichiers modifiés ont été rapprochés. Les messages d’audit ou les propositions sans réalisation attestée ne deviennent pas artificiellement des livraisons.'),
    p('Repères de sauvegarde', 'h2'),
]
source_rows = [[p('DATE', 'th'), p('RÉFÉRENCES ET CONTENU', 'th')]]
for date, refs, desc in SOURCES:
    source_rows.append([p(escape(date), 'date'), p(f'<b>{escape(refs)}</b><br/>{escape(desc)}', 'cell')])
source_table = LongTable(source_rows, colWidths=[84, W - 84], repeatRows=1)
source_table.setStyle(table_style())
story += [source_table, Spacer(1, 10),
    p('Convention de périmètre', 'h2'),
    p('L’application possédait déjà un socle métier lors de la première sauvegarde : comptes, catalogue, panier, commandes, import et administration. La simple présence d’un module dans ce premier commit ne prouve pas sa création depuis janvier ; seuls les changements identifiables sont décrits ici.'),
    p('Il peut exister d’autres travaux dans des sauvegardes, échanges ou dépôts non accessibles à ce relevé. Les périodes non documentées restent non chiffrées. Ce rapport est un inventaire des traces disponibles, pas une attestation exhaustive de toute l’activité depuis janvier.', 'small'),
]

doc.build(story, canvasmaker=NumberedCanvas)
manifest = {
    'pdf': str(TARGET), 'as_of': '2026-10-09', 'interventions': len(ROWS),
    'estimate_hours_min': low, 'estimate_hours_max': high,
    'periods': {key: {'count': len([r for r in ROWS if r[0] == key]), 'hours': total([r for r in ROWS if r[0] == key])} for key, _, _ in PERIODS},
}
(Path(__file__).parent / 'manifest.json').write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps(manifest, ensure_ascii=False))
