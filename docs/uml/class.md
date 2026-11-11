# Domain classes

```mermaid
classDiagram
  User "1" --> "0..1" StudentProfile
  User "1" --> "*" Cv
  User "1" --> "*" Application
  Company "1" --> "*" Opportunity
  Opportunity "1" --> "*" Application
  Application "*" --> "1" Cv
  Application "1" --> "*" ApplicationHistory
  Application "1" --> "0..1" Offer
  Offer "1" --> "0..1" PlacementRecord
  ApplicationWorkflow --> Application
  ReminderScheduler --> WorkflowActivity
```
