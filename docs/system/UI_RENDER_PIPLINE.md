# ⚙️ UI RENDER PIPELINE — LANDINGFLOW

## Version
v1.0 — Deterministic UI Generation Lifecycle

---

# 🎯 PURPOSE

This pipeline defines the full lifecycle of every UI element in LandingFlow:

From design → to implementation → to validation → to approval.

No UI is valid unless it passes through ALL stages.

---

# 🧠 CORE PRINCIPLE

UI is NOT generated directly.

UI is always:

Design → Built → Tested → Validated → Approved

Any step skipped = INVALID OUTPUT.

---

# 🔄 FULL PIPELINE OVERVIEW

```
1. DESIGN PHASE
      ↓
2. IMPLEMENTATION PHASE
      ↓
3. INTEGRATION PHASE
      ↓
4. UI TESTING PHASE
      ↓
5. FINAL VALIDATION PHASE
      ↓
6. APPROVAL / RELEASE
```

---

# 1️⃣ DESIGN PHASE

## Responsible Agent
Design System + Orchestrator

## Input
- Feature requirement
- Page type (dashboard, public, auth, CRM)
- Data structure

## Output
- UI layout plan
- Components to be used
- Page structure definition

## Rules

Must define:

- Layout type (dashboard / public / form)
- Components list
- Data blocks (KPIs, tables, forms)
- Navigation structure

NO CODE is written here.

---

# 2️⃣ IMPLEMENTATION PHASE

## Responsible Agent
Builder Agent

## Input
- Design specification

## Output
- PHP views
- Controllers (if needed)
- HTML structure
- Component usage

## Rules

MUST follow:

- DESIGN_SYSTEM.md strictly
- No new UI patterns
- Only approved components
- No inline styling overrides

---

# 3️⃣ INTEGRATION PHASE

## Responsible Agent
ScanService / Backend Layer

## Responsibilities

- Connect UI to APIs
- Bind data to views
- Ensure DB queries work
- Ensure scanner results render correctly

## Output

- Functional UI with real data

---

# 4️⃣ UI TESTING PHASE

## Responsible Agent
UI Testing Agent (ui_testing_agent.md)

## Responsibilities

Validate:

- Design system compliance
- Navigation integrity
- Component consistency
- Forms correctness
- Layout structure
- Responsive behavior
- No UI errors

## Output

```json
{
  "status": "PASS | FAIL",
  "score": 0-100,
  "issues": []
}
```

---

# 5️⃣ FINAL VALIDATION PHASE

## Responsible Agent
FINAL VALIDATION AGENT

## Responsibilities

Verify full system integrity:

- PHP syntax
- Routing
- Backend integration
- Scanner system
- APIs
- Database
- UI Testing Agent result
- Console errors

## Output

✔ PASS → allowed to release  
❌ FAIL → return to build phase

---

# 6️⃣ APPROVAL / RELEASE PHASE

## Responsible Agent
Orchestrator

## Condition

Only runs if ALL previous stages pass.

## Output

- Module marked COMPLETE
- Audit log updated
- System state updated
- Release confirmation generated

---

# 🚨 FAILURE RULE

If ANY stage fails:

System must:

1. Stop pipeline
2. Log error
3. Identify failing stage
4. Fix issue
5. Restart pipeline from failing stage

No skipping allowed.

---

# 🧩 PIPELINE INPUT STRUCTURE

Every UI request must include:

```json
{
  "module": "",
  "page_type": "dashboard | public | auth | crm",
  "data_context": {},
  "required_components": [],
  "routes": []
}
```

---

# 📊 PIPELINE OUTPUT STRUCTURE

Final output must include:

```json
{
  "module": "",
  "status": "APPROVED | REJECTED",
  "stages": {
    "design": "PASS",
    "implementation": "PASS",
    "integration": "PASS",
    "ui_test": "PASS",
    "final_validation": "PASS"
  },
  "issues": [],
  "score": 0-100
}
```

---

# 🧠 DESIGN SYSTEM ENFORCEMENT

At ALL stages:

- DESIGN_SYSTEM.md is the single source of truth
- UI Testing Agent is mandatory gatekeeper
- No visual deviation allowed
- No new UI styles allowed

---

# 🔒 IMMUTABILITY RULE

Once a UI module is APPROVED:

- It becomes stable baseline
- Changes require re-running full pipeline
- Partial edits are forbidden without re-validation

---

# 🎯 FINAL GOAL

LandingFlow UI pipeline ensures:

- Predictable UI generation
- Zero visual drift
- Enterprise-grade SaaS consistency
- Full automation safety for AI agents