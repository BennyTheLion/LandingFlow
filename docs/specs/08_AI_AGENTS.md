# AI Agent System

## Main Agent: Orchestrator

Responsible for:
- Breaking tasks
- Assigning subtasks
- Validating output
- Ensuring completion

---

## Validation Loop (MANDATORY)

No feature is complete unless:

1. Build passes
2. Tests pass
3. Lint passes
4. Accessibility ≥ 95
5. Lighthouse ≥ 90
6. Security scan passes
7. No console errors
8. API errors = 0

If any fail → retry automatically

---

## QA Agent

Simulates user behavior:
- Click all buttons
- Fill all forms
- Navigate all pages
- Mobile + Desktop testing

---

## AI Reviewer Agent

Before commit:
- Code quality check
- Architecture review
- Security review
- Performance review

Must score ≥ 95/100
Otherwise: refactor required