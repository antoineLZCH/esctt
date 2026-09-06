## Agent skills

### Issue tracker

Issues and specs live in GitHub Issues; use the `gh` CLI. See `docs/agents/issue-tracker.md`.

### Triage labels

Use the canonical labels: `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, and `wontfix`. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: root `CONTEXT.md` and `docs/adr/`. See `docs/agents/domain.md`.

### Autonomy boundary

- Autonomous work rules and stop conditions: `docs/agents/autonomy-policy.md`.

### Site rebuild execution

For spec #16 and tickets #17–#37, claim only unblocked frontier work and coordinate parallel execution using `docs/agents/site-rebuild-parallelism.md`.

### Working style

- Presentation: apply `i-have-adhd` on every task; a direct `stop` or `normal mode` request overrides it.
- Coding: apply `ponytail`; keep the smallest safe change and verify according to risk.
- CLI first: when a supported CLI can scaffold a project or install dependencies, use it instead of hand-writing or copying boilerplate; reserve manual edits for project-specific integration.
- Orchestration: use `sol-orchestrator` only for complex, independently parallelizable work.
