import sys
import unittest

sys.path.insert(0, __file__.rsplit("/", 1)[0])
from docs_check import changelog_missing  # noqa: E402

T = "2026-10-25"


class ChangelogMissing(unittest.TestCase):
    def test_local_heading_present(self):
        self.assertFalse(changelog_missing(T, f"## {T}\n", "", {"app/x.php"}))

    def test_pr_adds_heading(self):
        self.assertFalse(changelog_missing(T, "## 2026-10-24\n", f"+## {T}\n+- Fix", {"app/x.php"}))

    def test_code_without_heading(self):
        self.assertTrue(changelog_missing(T, "## 2026-10-24\n", "", {"app/x.php"}))

    def test_docs_only_needs_no_entry(self):
        self.assertFalse(changelog_missing(T, "## 2026-10-24\n", "", {"docs/GDD.md"}))


if __name__ == "__main__":
    unittest.main()
