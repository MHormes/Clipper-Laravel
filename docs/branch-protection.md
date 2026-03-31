# GitHub Branch Protection Setup

This guide describes how to configure branch protection rules to enforce the CI/CD pipeline.

## Branch Strategy

| Branch        | Purpose                                                  |
| ------------- | -------------------------------------------------------- |
| `development` | Active development — linter + tests run on every push    |
| `main`        | Production-ready code — tests must pass before any merge |

---

## Protecting `main`

Go to your repository on GitHub: **Settings → Branches → Add branch ruleset**.

### Using Branch Rulesets (recommended, newer UI)

1. **Settings → Rules → Rulesets → New ruleset**
2. Name: `Protect main`
3. Target branches: `main`
4. Enable the following:
    - **Restrict creations** — prevents force-creating the branch
    - **Restrict deletions** — prevents accidental deletion
    - **Require a pull request before merging**
        - Optionally require 1 approving review
    - **Require status checks to pass**
        - Click "Add checks" and search for: `ci`
        - Enable: **Require branches to be up to date before merging**
    - **Block force pushes**
5. Save the ruleset.

> **Important:** The required status check name must exactly match the `jobs:` key in the workflow file. In `tests.yml` the job is named `ci` — that is the value to enter.

---

## Protecting `development` (optional)

Repeat the steps above with branch pattern `development`, but only enable:

- [x] Block force pushes
- [x] Restrict deletions

This prevents accidental history rewrites without blocking normal pushes.

---

## Verifying the Setup

1. Push any commit to `development` → check that both `linter` and `tests` workflows appear in the **Actions** tab.
2. Open a PR from `development` → `main` → confirm the `ci` check appears on the PR.
3. If the `ci` check is failing, the **Merge pull request** button should be greyed out with a message like _"Required status checks must pass before merging."_
4. Once the check passes, the merge button becomes available.
