"""Erzeugt das WH-Company-Logo als SVG (Schrift Forum, OFL, in Pfade umgewandelt)."""
import math, sys
from fontTools.ttLib import TTFont
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from fontTools.pens.boundsPen import BoundsPen

font = TTFont(sys.argv[1]); out = sys.argv[2]
gs = font.getGlyphSet(); cmap = font.getBestCmap(); upm = font['head'].unitsPerEm
cap = font['OS/2'].sCapHeight or 700
ORANGE = '#d97824'

def text_path(text, x, baseline, cap_px, tracking_em=0.0, anchor='start'):
    s = cap_px / cap
    # Breite zuerst bestimmen (für zentrieren)
    adv = []
    for ch in text:
        g = cmap[ord(ch)]; adv.append((g, gs[g].width))
    total = sum(a for _, a in adv) * s + tracking_em * cap_px * (len(text) - 1)
    if anchor == 'middle': x -= total / 2
    pen = SVGPathPen(gs, ntos=lambda v: ('%.1f' % v).rstrip('0').rstrip('.'))
    cx = x
    for g, a in adv:
        tp = TransformPen(pen, (s, 0, 0, -s, cx, baseline))
        gs[g].draw(tp)
        cx += a * s + tracking_em * cap_px
    return pen.getCommands(), total

def spiral(cx, cy, r0, turns, direction):
    pts = []
    n = 60
    for i in range(n + 1):
        t = i / n
        a = -math.pi / 2 + direction * t * turns * 2 * math.pi
        r = r0 * (1 - 0.78 * t)
        pts.append((cx + r * math.cos(a), cy + r * math.sin(a)))
    return 'M' + ' L'.join(f'{x:.2f} {y:.2f}' for x, y in pts)

def column(sw):
    # Ionische Säule: Abakus, zwei Voluten, Echinus, kannelierter Schaft, Basis
    p = []
    p.append('M120 64 H202')                              # Abakus
    p.append(spiral(132, 76, 11.5, 1.6, -1))               # linke Volute (gegen Uhrzeiger nach innen)
    p.append(spiral(190, 76, 11.5, 1.6, 1))                # rechte Volute
    p.append('M132 64.5 Q161 74 190 64.5')                 # Polster zwischen den Voluten
    p.append('M136 90 H186')                               # Hals
    for x in (141, 149.5, 158, 166.5, 175, 183.5)[:5]:
        p.append(f'M{x+2:.1f} 94 V132')                    # Kanneluren
    p.append('M136 135 H186')                              # Basis
    return f'<path d="{" ".join(p)}" fill="none" stroke="{ORANGE}" stroke-width="{sw}" stroke-linecap="round" stroke-linejoin="round"/>'

wh, wh_width = text_path('WH', 247, 131, 66)
company, _ = text_path('WH-COMPANY', 244, 219, 19, tracking_em=0.62, anchor='middle')
tag, _ = text_path('SOFTWARE ARCHITECTURE IN PERFECTION', 242, 246, 8.6, tracking_em=0.30, anchor='middle')

full = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="68 22 350 236" role="img" aria-labelledby="t">
<title id="t">WH-Company – Software Architecture in Perfection</title>
{column(2.4)}
<path d="M235 32 V168" stroke="{ORANGE}" stroke-width="2.2" stroke-linecap="round"/>
<path d="{wh}" fill="{ORANGE}"/>
<path d="{company}" fill="{ORANGE}"/>
<path d="{tag}" fill="{ORANGE}"/>
</svg>
'''
mark_right = 247 + wh_width + 6
mark = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="108 22 {mark_right - 108:.0f} 156" role="img" aria-labelledby="t">
<title id="t">WH-Company</title>
{column(3.4)}
<path d="M235 32 V168" stroke="{ORANGE}" stroke-width="3" stroke-linecap="round"/>
<path d="{wh}" fill="{ORANGE}"/>
</svg>
'''
icon = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="111 50 100 100">
<title>WH-Company</title>
{column(6).replace('stroke-width="6"', 'stroke-width="6.5"')}
</svg>
'''
open(out + '/logo.svg', 'w').write(full)
open(out + '/logo-mark.svg', 'w').write(mark)
open(out + '/favicon.svg', 'w').write(icon)
