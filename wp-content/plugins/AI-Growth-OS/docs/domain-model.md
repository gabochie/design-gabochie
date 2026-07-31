# AI Growth OS — Domain Model

## Core Aggregates

### Agent Aggregate
- **Agent** (root) — id, name, role, mission, status
  - AgentMemory — short/long-term memory, context
  - TaskQueue — prioritized tasks
  - ExecutionHistory — past runs, decisions, outcomes
  - AgentTool — tools the agent can invoke
  - ApprovalWorkflow — required approvals

### Audit Aggregate
- **AuditRun** (root) — id, type, status, started/completed
  - AuditResult — findings, severity, category
  - AuditScope — what was audited (page, post, product, etc.)

### Recommendation Aggregate
- **Recommendation** (root) — id, title, description, category
  - PriorityScore — impact, effort, confidence, urgency
  - RecommendationStatus — draft, approved, rejected, implemented

### Workflow Aggregate
- **Workflow** (root) — id, name, trigger, schedule
  - WorkflowStep — action, conditions, order
  - WorkflowExecution — status, result, logs

### Score Aggregate
- **Score** (root) — type, value, trend, timestamp
  - ScoreDimension — sub-scores and weights

### Event (Domain Events)
- AuditCompleted
- RecommendationCreated
- AgentTaskStarted
- AgentTaskCompleted
- WorkflowTriggered
- WorkflowCompleted
- ScoreUpdated
