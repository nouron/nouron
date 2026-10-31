import os
import sys
import tempfile
import time
import unittest

sys.path.insert(0, os.path.dirname(__file__))
from docs_check import ci_problems, todo_markers  # noqa: E402
from guards import bot_problem, dev_db_problem, target_db  # noqa: E402


def env_dir(db):
    d = tempfile.mkdtemp()
    open(os.path.join(d, ".env"), "w").write(f"APP_ENV=local\nDB_DATABASE={db}\n")
    return d


class DevDbGuard(unittest.TestCase):
    def test_fresh_on_dev_db_is_a_problem(self):
        self.assertIsNotNone(dev_db_problem("php artisan migrate:fresh --seed", env_dir("nouron")))

    def test_reset_player_on_dev_db_is_a_problem(self):
        self.assertIsNotNone(dev_db_problem("php artisan game:reset-player Bart --yes", env_dir("nouron")))

    def test_throwaway_db_in_env_is_fine(self):
        self.assertIsNone(dev_db_problem("php artisan migrate:fresh", env_dir("nouron_g1_unused")))

    def test_inline_db_override_wins(self):
        self.assertIsNone(dev_db_problem("DB_DATABASE=nouron_x php artisan db:seed", env_dir("nouron")))

    def test_cd_into_worktree_uses_its_env(self):
        d = env_dir("nouron_a1_unused")
        self.assertIsNone(dev_db_problem(f"cd {d} && php artisan migrate:fresh", env_dir("nouron")))

    def test_owner_marker_allows(self):
        self.assertIsNone(dev_db_problem("NOURON_ALLOW_DEV_DB=1 php artisan migrate:fresh", env_dir("nouron")))

    def test_plain_migrate_is_not_destructive(self):
        self.assertIsNone(dev_db_problem("php artisan migrate --force", env_dir("nouron")))

    def test_unknown_db_is_a_problem(self):
        self.assertIsNotNone(dev_db_problem("php artisan db:wipe", tempfile.mkdtemp()))
        self.assertIsNone(target_db("ls", tempfile.mkdtemp()))


class BotGuard(unittest.TestCase):
    def test_playtest_without_approval(self):
        self.assertIsNotNone(bot_problem("php artisan game:playtest --seeds=8", "/nonexistent/.bot-approved"))

    def test_fresh_approval_allows(self):
        f = tempfile.NamedTemporaryFile()
        self.assertIsNone(bot_problem("php artisan game:playtest", f.name))

    def test_stale_approval_is_a_problem(self):
        f = tempfile.NamedTemporaryFile()
        self.assertIsNotNone(bot_problem("php artisan game:playtest", f.name, now=time.time() + 4 * 3600))

    def test_other_commands_unaffected(self):
        self.assertIsNone(bot_problem("php artisan game:tick", "/nonexistent"))


class MergeChecks(unittest.TestCase):
    def test_ci_buckets(self):
        failing, pending = ci_problems([{"name": "lint", "bucket": "pass"}, {"name": "tests", "bucket": "fail"}, {"name": "e2e", "bucket": "pending"}])
        self.assertEqual((failing, pending), (["tests"], ["e2e"]))

    def test_todo_markers_count_only_added_lines(self):
        diff = "+++ b/lang/de/x.php\n+    'a' => 'x', // TODO content-writer\n-    'b' => 'y', // TODO content-writer\n context TODO content-writer\n"
        self.assertEqual(todo_markers(diff), 1)


if __name__ == "__main__":
    unittest.main()
