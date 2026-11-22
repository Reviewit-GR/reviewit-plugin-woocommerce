#!/usr/bin/env python3
"""Compiles languages/*.po into .mo files (WordPress reads only .mo). No gettext needed: python3 bin/compile-mo.py"""
import ast, glob, struct, sys

def parse(path):
    msgs, cur, key, val = {}, None, None, None
    def flush():
        if key is not None and (val or key == ''):
            msgs[key] = val
    for line in open(path, encoding='utf-8'):
        line = line.strip()
        if line.startswith('msgid '):
            flush(); key, val, cur = ast.literal_eval(line[6:]), '', 'id'
        elif line.startswith('msgstr '):
            val, cur = ast.literal_eval(line[7:]), 'str'
        elif line.startswith('"'):
            s = ast.literal_eval(line)
            if cur == 'id': key += s
            else: val += s
    flush()
    return msgs

def write(msgs, out):
    keys = sorted(msgs)
    ids = b''; strs = b''; offs = []
    for k in keys:
        kb, vb = k.encode(), msgs[k].encode()
        offs.append((len(ids), len(kb), len(strs), len(vb)))
        ids += kb + b'\0'; strs += vb + b'\0'
    n = len(keys); start = 7 * 4 + 16 * n
    data = struct.pack('Iiiiiii', 0x950412de, 0, n, 28, 28 + 8 * n, 0, 0)
    data += b''.join(struct.pack('ii', l, start + o) for o, l, _, _ in offs)
    data += b''.join(struct.pack('ii', l, start + len(ids) + o) for _, _, o, l in offs)
    open(out, 'wb').write(data + ids + strs)

for po in glob.glob('languages/*.po'):
    write(parse(po), po[:-3] + '.mo'); print('wrote', po[:-3] + '.mo')
