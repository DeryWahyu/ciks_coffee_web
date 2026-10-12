import importlib.util
import io
from pathlib import Path
import tarfile
import tempfile
import unittest

spec = importlib.util.spec_from_file_location('validator', Path(__file__).parents[1] / 'validate-release.py')
validator = importlib.util.module_from_spec(spec)
spec.loader.exec_module(validator)
REVISION = 'a' * 40


class ReleaseArchiveTest(unittest.TestCase):
    def bundle(self, extra):
        workspace = tempfile.TemporaryDirectory()
        self.addCleanup(workspace.cleanup)
        path = Path(workspace.name) / 'release.tar.gz'
        required = {'artisan': b'php', 'vendor/autoload.php': b'php',
                    'public/index.php': b'php', 'public/build/manifest.json': b'{}',
                    'composer.lock': b'{}', '.release-revision': (REVISION + '\n').encode()}
        with tarfile.open(path, 'w:gz') as bundle:
            for name, content in required.items():
                member = tarfile.TarInfo('./' + name)
                member.size = len(content)
                bundle.addfile(member, io.BytesIO(content))
            for member in extra:
                bundle.addfile(member, io.BytesIO(b'x' * member.size) if member.isfile() else None)
        return path

    def test_accepts_vendor_binary_symlink_within_release(self):
        member = tarfile.TarInfo('vendor/bin/psysh')
        member.type = tarfile.SYMTYPE
        member.linkname = '../psy/psysh/bin/psysh'
        validator.validate(self.bundle([member]), REVISION)

    def test_rejects_parent_path(self):
        with self.assertRaises(ValueError):
            validator.validate(self.bundle([tarfile.TarInfo('../.env')]), REVISION)

    def test_rejects_symlink_to_server_secrets(self):
        member = tarfile.TarInfo('public/leak')
        member.type = tarfile.SYMTYPE
        member.linkname = '/var/www/cikscoffee-shared/.env'
        with self.assertRaises(ValueError):
            validator.validate(self.bundle([member]), REVISION)

    def test_rejects_injected_shared_paths(self):
        for path in ['.env', 'storage/app/private/firebase/service-account.json',
                     'public/downloads/CiksCoffee-latest.apk', 'bootstrap/cache/config.php']:
            with self.subTest(path=path), self.assertRaises(ValueError):
                validator.validate(self.bundle([tarfile.TarInfo(path)]), REVISION)

    def test_rejects_duplicate_revision(self):
        with self.assertRaises(ValueError):
            validator.validate(self.bundle([tarfile.TarInfo('.release-revision')]), REVISION)

    def test_rejects_wrong_commit(self):
        with self.assertRaises(ValueError):
            validator.validate(self.bundle([]), 'b' * 40)

    def test_rejects_symlink_as_archive_root(self):
        member = tarfile.TarInfo('.')
        member.type = tarfile.SYMTYPE
        member.linkname = '/etc'
        with self.assertRaises(ValueError):
            validator.validate(self.bundle([member]), REVISION)


if __name__ == '__main__':
    unittest.main()
