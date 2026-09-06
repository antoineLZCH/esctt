---
name: sol-orchestrator
description: Use for complex coding tasks with bounded subagents.
version: 0.1.0
author: donvito, Antoine Lzch, Hermes Agent
license: MIT
platforms: [linux, macos, windows]
metadata:
  hermes:
    tags: [orchestration, delegation, coding, sol, luna]
---

# Sol Orchestrator

Use Sol as the root orchestrator and final integrator. Use Luna for bounded exploration, implementation, testing, and research. The user's explicit instructions always take precedence.

This is the Hermes adaptation of `donvito/codex-astra-luna-orchestrator`: Astra has been replaced by Sol. It defines a workflow; it does not change Hermes' active model configuration.

## When to use

Use for multi-file features, cross-component debugging, repository-wide changes, independent research, or tasks where separate verification improves confidence.

Do not delegate trivial edits, simple questions, or work with no independent boundary.

## Model roles

- **root / orchestrator:** GPT-5.6 Sol (`gpt-5.6-sol`), medium reasoning.
- **explorer:** GPT-5.6 Luna (`gpt-5.6-luna`), medium reasoning, read-only.
- **worker:** GPT-5.6 Luna (`gpt-5.6-luna`), medium reasoning, bounded workspace writes.
- **tester:** GPT-5.6 Luna (`gpt-5.6-luna`), medium reasoning.
- **researcher:** GPT-5.6 Luna (`gpt-5.6-luna`), medium reasoning.
- **reviewer:** GPT-5.6 Sol (`gpt-5.6-sol`), medium reasoning.

When the runtime cannot pin a delegated model, keep the role contract and state that model pinning was unavailable; never claim it happened.

## Delegation contract

Every `delegate_task` call must specify:

1. **Objective:** one concrete outcome.
2. **Scope:** exact files, subsystem, or question.
3. **Context:** only facts needed to act.
4. **Constraints:** what must not change.
5. **Deliverable:** the expected report or artifact.
6. **Acceptance:** how completion will be checked.

Keep tasks narrow. Subagents provide evidence or bounded changes; Sol owns architecture, conflict resolution, integration, and the final answer.

## Roles

- **Explorer:** map relevant files, symbols, data flow, tests, and constraints; do not edit.
- **Worker:** implement one bounded change; avoid unrelated refactors.
- **Tester:** reproduce and verify; do not silently expand scope.
- **Researcher:** verify current external behavior from primary sources.
- **Reviewer:** independently inspect the final diff for correctness, security, regressions, and missing tests.

## Parallelism

Run independent read-only tasks in parallel. Serialize dependent work:

1. explore;
2. decide the smallest valid change;
3. implement;
4. test;
5. review;
6. fix material findings;
7. verify the final state.

Never assign the same files to multiple writers concurrently.

## Completion

Before claiming success, Sol must inspect the final diff, confirm the requested behavior, run the highest-value verification, and state anything not tested. Prefer the smallest solution that works; preserve validation, security, error handling, accessibility, and explicit requirements.
