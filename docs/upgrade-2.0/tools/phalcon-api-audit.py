#!/usr/bin/env python3
"""
Liste les classes Phalcon référencées dans un répertoire source et indique
celles qui n'existent plus (ou ne sont plus qu'un namespace) dans les stubs Phalcon 5.

Usage :
    composer require --dev phalcon/ide-stubs:^5      # ou dans un dossier temporaire
    python3 docs/upgrade-2.0/tools/phalcon-api-audit.py <stubs/src> [src/Neutrino/<Module>]
"""
import collections
import os
import re
import sys

if len(sys.argv) < 2:
    sys.exit(__doc__)

stubs = sys.argv[1]
target = sys.argv[2] if len(sys.argv) > 2 else "src/Neutrino"

uses = collections.defaultdict(set)
for root, _, files in os.walk(target):
    for name in files:
        if not name.endswith(".php"):
            continue
        path = os.path.join(root, name)
        with open(path, errors="ignore") as fh:
            for cls in re.findall(r"\\?(Phalcon(?:\\[A-Za-z_]+)+)", fh.read()):
                uses[cls.rstrip("\\")].add(os.path.relpath(path, target))

missing, namespace_only = [], []
for cls in sorted(uses):
    rel = cls[len("Phalcon\\"):].replace("\\", "/")
    if os.path.isfile(f"{stubs}/{rel}.php"):
        continue
    (namespace_only if os.path.isdir(f"{stubs}/{rel}") else missing).append(cls)

def dump(title, classes):
    print(f"## {title} ({len(classes)})")
    for cls in classes:
        print(f"- `{cls}` ← {', '.join(sorted(uses[cls]))}")
    print()

print(f"{len(uses)} classes Phalcon référencées dans {target}\n")
dump("Supprimées ou déplacées", missing)
dump("Devenues un namespace (classe déplacée dedans)", namespace_only)
