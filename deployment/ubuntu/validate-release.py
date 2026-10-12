#!/usr/bin/env python3
"""Reject unsafe tar entries before extracting a CI release as the app owner."""
import posixpath
import re
import sys
import tarfile


def validate(archive, revision):
    if not re.fullmatch(r'[0-9a-f]{40}', revision):
        raise ValueError('Invalid revision')
    names = set()
    total = 0
    with tarfile.open(archive, 'r:gz') as bundle:
        members = bundle.getmembers()
        if len(members) > 40000:
            raise ValueError('Too many archive entries')
        for member in members:
            name = member.name
            if name.startswith('/') or '..' in name.split('/'):
                raise ValueError('Archive path escapes release')
            name = posixpath.normpath(name)
            if name == '.':
                if not member.isdir():
                    raise ValueError('Archive root must be a directory')
                continue
            if name in names:
                raise ValueError('Duplicate archive path')
            names.add(name)
            root = name.split('/')[0]
            if root in {'.git', '.github', 'storage', 'node_modules'} or root.startswith('.env'):
                raise ValueError('Runtime or secret files in archive')
            if any(name == prefix or name.startswith(prefix + '/') for prefix in
                   ('public/storage', 'public/downloads', 'bootstrap/cache')):
                raise ValueError('Shared runtime path in archive')
            if not (member.isfile() or member.isdir() or member.issym()):
                raise ValueError('Unsupported archive entry')
            if member.issym():
                target = member.linkname
                destination = posixpath.normpath(posixpath.join(posixpath.dirname(name), target))
                if target.startswith('/') or destination == '..' or destination.startswith('../'):
                    raise ValueError('Symlink escapes release')
            total += member.size
            if total > 768 * 1024 * 1024:
                raise ValueError('Unpacked release exceeds size limit')
        required = {'artisan', 'vendor/autoload.php', 'public/index.php',
                    'public/build/manifest.json', 'composer.lock', '.release-revision'}
        if not required.issubset(names):
            raise ValueError('Incomplete production release')
        for name in required:
            member = next(m for m in members if posixpath.normpath(m.name) == name)
            if not member.isfile():
                raise ValueError('Required release files must be regular files')
        stamp = next(m for m in members if posixpath.normpath(m.name) == '.release-revision')
        if stamp.size > 128 or bundle.extractfile(stamp).read().decode().strip() != revision:
            raise ValueError('Revision does not match deployment request')


if __name__ == '__main__':
    validate(sys.argv[1], sys.argv[2])
    print('Release archive validated')
