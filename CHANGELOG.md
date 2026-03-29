# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [2.0.0] - 2026-03-29

### Added

- **Domain model**: WorkflowCampaign, WorkflowRun, WorkflowTriggerLog entities with Doctrine XML mappings
- **Graph engine**: directed graph executor with node-by-node traversal
- **Node types**: Trigger, Condition, Action, Delay with typed PHP classes
- **6 rule evaluators**: OrderTotal, CustomerFirstOrder, CustomerTag, CustomerCountry, LoyaltyTier, WorkflowRunCount
- **7 action executors**: SendEmail, GenerateCoupon, AddCustomerTag, RemoveCustomerTag, AddLoyaltyPoints, SendWebhook, AddOrderNote
- **Graph validator**: cycle detection (DFS), trigger count validation, edge/node integrity checks
- **Delay handling**: Symfony Messenger with DelayStamp for deferred execution
- **Deduplication**: SHA-256 hash-based trigger log prevents double-firing
- **Visual canvas editor**: React 18 + React Flow v12 node-based graph editor
- **Custom node components**: color-coded nodes (purple/amber/teal/gray) with handles
- **Right config panel**: dynamic fields per node type with auto-generated descriptions
- **Canvas toolbar**: inline name editing, add node dropdown, auto-arrange, save, activate toggle
- **Frontend validation**: cycle detection and trigger validation before save
- **Admin CRUD**: Sylius Grid workflow list with status badges, enabled toggle, action buttons
- **Create workflow form**: name + description, auto-creates trigger node, redirects to canvas
- **Run log viewer**: per-campaign run list with status badges and execution timeline detail
- **Analytics dashboard**: 30-day stats cards, daily runs chart (Chart.js), per-workflow breakdown
- **Admin menu**: "Automation Workflows" under Marketing sidebar section
- **8 workflow templates**: AbandonedCartRecovery, PostPurchaseReviewRequest, WinBackInactiveCustomer, BirthdayCoupon, LoyaltyTierUpgrade, NewCustomerWelcome, PostPurchaseUpsell, PaymentFailedRecovery
- **Template browser**: admin page to browse and install pre-built workflow templates
- **Test run feature**: dry-run executor with canvas path highlighting (green/amber borders)
- **8 email templates**: shared base layout with per-workflow Twig templates
- **DryRunWorkflowExecutor**: evaluates conditions, skips actions, traces execution path
- **Graceful degradation**: loyalty plugin integration via class_exists(), email failures logged but don't stop workflow
- **CI pipeline**: PHPStan level 5, PHPUnit, JS build verification across PHP 8.2/8.3/8.4
