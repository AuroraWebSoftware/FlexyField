# OpenSpec Instructions

Instructions for AI coding assistants using OpenSpec for spec-driven development.

## TL;DR Quick Checklist

- Search existing work: `openspec spec list --long`, `openspec list` (use `rg` only for full-text search)
- Decide scope: new capability vs modify existing capability
- Pick a unique `change-id`: kebab-case, verb-led (`add-`, `update-`, `remove-`, `refactor-`)
- Scaffold: `proposal.md`, `tasks.md`, `design.md` (only if needed), and delta specs per affected capability
- Write deltas: use `## ADDED|MODIFIED|REMOVED|RENAMED Requirements`; include at least one `#### Scenario:` per requirement
- Validate: `openspec validate [change-id] --strict` and fix issues
- Request approval: Do not start implementation until proposal is approved

## Three-Stage Workflow

### Stage 1: Creating Changes
Create proposal when you need to:
- Add features or functionality
- Make breaking changes (API, schema)
- Change architecture or patterns  
- Optimize performance (changes behavior)
- Update security patterns

Triggers (examples):
- "Help me create a change proposal"
- "Help me plan a change"
- "Help me create a proposal"
- "I want to create a spec proposal"
- "I want to create a spec"

Loose matching guidance:
- Contains one of: `proposal`, `change`, `spec`
- With one of: `create`, `plan`, `make`, `start`, `help`

Skip proposal for:
- Bug fixes (restore intended behavior)
- Typos, formatting, comments
- Dependency updates (non-breaking)
- Configuration changes
- Tests for existing behavior

**Workflow**
1. Review `openspec/project.md`, `openspec list`, and `openspec list --specs` to understand current context.
2. Choose a unique verb-led `change-id` and scaffold `proposal.md`, `tasks.md`, optional `design.md`, and spec deltas under `openspec/changes/<id>/`.
3. Draft spec deltas using `## ADDED|MODIFIED|REMOVED Requirements` with at least one `#### Scenario:` per requirement.
4. Run `openspec validate <id> --strict` and resolve any issues before sharing the proposal.

### Stage 2: Implementing Changes
Track these steps as TODOs and complete them one by one.
1. **Read proposal.md** - Understand what's being built
2. **Read design.md** (if exists) - Review technical decisions
3. **Read tasks.md** - Get implementation checklist
4. **Implement tasks sequentially** - Complete in order
5. **Confirm completion** - Ensure every item in `tasks.md` is finished before updating statuses
6. **Update checklist** - After all work is done, set every task to `- [x]` so the list reflects reality
7. **Approval gate** - Do not start implementation until the proposal is reviewed and approved

### Stage 3: Archiving Changes
After deployment, create separate PR to:
- Move `changes/[name]/` → `changes/archive/YYYY-MM-DD-[name]/`
- Update `specs/` if capabilities changed
- Use `openspec archive <change-id> --skip-specs --yes` for tooling-only changes (always pass the change ID explicitly)
- Run `openspec validate --strict` to confirm the archived change passes checks

## Before Any Task

**Context Checklist:**
- [ ] Read relevant specs in `specs/[capability]/spec.md`
- [ ] Check pending changes in `changes/` for conflicts
- [ ] Read `openspec/project.md` for conventions
- [ ] Run `openspec list` to see active changes
- [ ] Run `openspec list --specs` to see existing capabilities

**Before Creating Specs:**
- Always check if capability already exists
- Prefer modifying existing specs over creating duplicates
- Use `openspec show [spec]` to review current state
- If request is ambiguous, ask 1–2 clarifying questions before scaffolding

### Search Guidance
- Enumerate specs: `openspec spec list --long` (or `--json` for scripts)
- Enumerate changes: `openspec list` (or `openspec change list --json` - deprecated but available)
- Show details:
  - Spec: `openspec show <spec-id> --type spec` (use `--json` for filters)
  - Change: `openspec show <change-id> --json --deltas-only`
- Full-text search (use ripgrep): `rg -n "Requirement:|Scenario:" openspec/specs`

## Quick Start

### CLI Commands

```bash
# Essential commands
openspec list                  # List active changes
openspec list --specs          # List specifications
openspec show [item]           # Display change or spec
openspec validate [item]       # Validate changes or specs
openspec archive <change-id> [--yes|-y]   # Archive after deployment (add --yes for non-interactive runs)

# Project management
openspec init [path]           # Initialize OpenSpec
openspec update [path]         # Update instruction files

# Interactive mode
openspec show                  # Prompts for selection
openspec validate              # Bulk validation mode

# Debugging
openspec show [change] --json --deltas-only
openspec validate [change] --strict
```

### Command Flags

- `--json` - Machine-readable output
- `--type change|spec` - Disambiguate items
- `--strict` - Comprehensive validation
- `--no-interactive` - Disable prompts
- `--skip-specs` - Archive without spec updates
- `--yes`/`-y` - Skip confirmation prompts (non-interactive archive)

## Directory Structure

```
openspec/
├── project.md              # Project conventions
├── specs/                  # Current truth - what IS built
│   └── [capability]/       # Single focused capability
│       ├── spec.md         # Requirements and scenarios
│       └── design.md       # Technical patterns
├── changes/                # Proposals - what SHOULD change
│   ├── [change-name]/
│   │   ├── proposal.md     # Why, what, impact
│   │   ├── tasks.md        # Implementation checklist
│   │   ├── design.md       # Technical decisions (optional; see criteria)
│   │   └── specs/          # Delta changes
│   │       └── [capability]/
│   │           └── spec.md # ADDED/MODIFIED/REMOVED
│   └── archive/            # Completed changes
```

## Creating Change Proposals

### Decision Tree

```
New request?
├─ Bug fix restoring spec behavior? → Fix directly
├─ Typo/format/comment? → Fix directly  
├─ New feature/capability? → Create proposal
├─ Breaking change? → Create proposal
├─ Architecture change? → Create proposal
└─ Unclear? → Create proposal (safer)
```

### Proposal Structure

**Step 1: Create directory**
```bash
mkdir -p openspec/changes/add-new-feature/specs/capability-name
```
- Use kebab-case, verb-led naming: `add-`, `update-`, `remove-`, `refactor-`
- Ensure uniqueness: check `openspec list` first
- Create `specs/[capability]/` subdirectory for each affected capability

**Step 2: Write proposal.md**
```markdown
# Change: [Brief description of change]

## Why
[1-2 sentences on problem/opportunity]
- Explain the motivation clearly
- Reference related issues or requirements if applicable

## What Changes
- [Bullet list of changes]
- [Mark breaking changes with **BREAKING**]
- Be specific about what files/systems are affected

## Impact
- Affected specs: [list capabilities, e.g., "field-validation, type-system"]
- Affected code: [key files/systems, e.g., "src/Traits/Flexy.php, database migrations"]
- Breaking changes: [list if any, e.g., "Removes deprecated method X"]
- Testing requirements: [describe what tests need to be created/updated]
- Documentation updates: [describe what documentation needs to be updated]
```

**Example proposal.md:**
```markdown
# Change: Add File Field Type

## Why
Users need to store file references (paths, URLs) as flexy fields. Currently only basic types (STRING, INTEGER, etc.) are supported. Adding FILE type enables file management use cases.

## What Changes
- Add FILE to FlexyFieldType enum
- Add value_file column to ff_values table
- Update type detection logic to handle file paths
- Add file validation rules support

## Impact
- Affected specs: type-system, dynamic-field-storage, field-validation
- Affected code: src/Enums/FlexyFieldType.php, database/migrations/create_flexyfield_table.php, src/Traits/Flexy.php
- Breaking changes: None
```

**Step 3: Create spec deltas**

For each affected capability, create `specs/[capability]/spec.md`:

```markdown
## ADDED Requirements
### Requirement: New Feature Name
The system SHALL provide [clear description of capability].

#### Scenario: Success case name
- **WHEN** [trigger condition]
- **THEN** [expected outcome]
- **AND** [additional outcome if needed]

## MODIFIED Requirements
### Requirement: Existing Feature Name
[Complete modified requirement - copy entire requirement from openspec/specs/<capability>/spec.md and edit]

#### Scenario: Updated scenario name
- **WHEN** [updated condition]
- **THEN** [updated outcome]

## REMOVED Requirements
### Requirement: Old Feature Name
**Reason**: [Why removing - be specific]
**Migration**: [How to handle - provide clear migration path]

## RENAMED Requirements
- FROM: `### Requirement: Old Name`
- TO: `### Requirement: New Name`
```

**Important:** If multiple capabilities are affected, create multiple delta files:
```
changes/add-feature/
├── proposal.md
├── tasks.md
└── specs/
    ├── type-system/
    │   └── spec.md   # ADDED: New field type
    └── field-validation/
        └── spec.md   # MODIFIED: Validation rules
```

**Example spec delta (ADDED):**
```markdown
## ADDED Requirements
### Requirement: File Field Type Support
The system SHALL support FILE field type for storing file references (paths, URLs) in flexy fields.

#### Scenario: File path is stored
- **WHEN** a file path string is assigned to a FILE type flexy field
- **THEN** it SHALL be stored in the value_file column
- **AND** it SHALL be retrieved as a string

#### Scenario: File validation rules are applied
- **WHEN** a FILE field has validation rules defined in its field set
- **THEN** validation SHALL be applied using Laravel's file validation rules
- **AND** validation errors SHALL be thrown as ValidationException
```

**Example spec delta (MODIFIED):**
```markdown
## MODIFIED Requirements
### Requirement: Supported Field Types
The system SHALL support multiple data types for flexy fields via the FlexyFieldType enum, with type safety enforced through field set definitions. Supported types include STRING, INTEGER, DECIMAL, DATE, DATETIME, BOOLEAN, JSON, and FILE.

#### Scenario: String type is supported
- **WHEN** a string value is assigned to a flexy field
- **THEN** it SHALL be stored in the value_string column
- **AND** the value SHALL be retrieved as a string

#### Scenario: File type is supported
- **WHEN** a file path is assigned to a FILE type flexy field
- **THEN** it SHALL be stored in the value_file column
- **AND** the value SHALL be retrieved as a string
```

4. **Create tasks.md:**
```markdown
## 1. Implementation
- [ ] 1.1 Create database schema
- [ ] 1.2 Implement API endpoint
- [ ] 1.3 Add frontend component

## 2. Testing
- [ ] 2.1 Write unit tests for new functionality
- [ ] 2.2 Write integration tests
- [ ] 2.3 Write feature tests for user workflows
- [ ] 2.4 Update existing tests if needed

## 3. Documentation
- [ ] 3.1 Update README.md with new feature documentation
- [ ] 3.2 Update Laravel Boost core.blade.php with AI guidance
- [ ] 3.3 Add code examples to documentation
- [ ] 3.4 Update changelog
```

**Tasks.md Template (copy and modify for your proposal):**
```markdown
## 1. Implementation
- [ ] 1.1 [First implementation step]
- [ ] 1.2 [Second implementation step]
- [ ] 1.3 [Additional implementation steps as needed]

## 2. Testing (Mandatory)
- [ ] 2.1 Write unit tests for [specific component/method]
- [ ] 2.2 Write integration tests for [component interactions]
- [ ] 2.3 Write feature tests for [user workflow]
- [ ] 2.4 Update existing tests affected by this change
- [ ] 2.5 Verify test coverage meets minimum requirements

## 3. Documentation (Mandatory)
- [ ] 3.1 Update README.md with [new feature] documentation
- [ ] 3.2 Update Laravel Boost core.blade.php with AI guidance
- [ ] 3.3 Add code examples for [new functionality]
- [ ] 3.4 Update CHANGELOG.md with [breaking changes/new features]
- [ ] 3.5 Verify documentation examples are tested and working
```

5. **Create design.md when needed:**
Create `design.md` if any of the following apply; otherwise omit it:
- Cross-cutting change (multiple services/modules) or a new architectural pattern
- New external dependency or significant data model changes
- Security, performance, or migration complexity
- Ambiguity that benefits from technical decisions before coding

Minimal `design.md` skeleton:
```markdown
## Context
[Background, constraints, stakeholders]

## Goals / Non-Goals
- Goals: [...]
- Non-Goals: [...]

## Decisions
- Decision: [What and why]
- Alternatives considered: [Options + rationale]

## Risks / Trade-offs
- [Risk] → Mitigation

## Migration Plan
[Steps, rollback]

## Open Questions
- [...]
```

## Spec File Format

### Critical: Scenario Formatting

**CORRECT** (use #### headers):
```markdown
#### Scenario: User login success
- **WHEN** valid credentials provided
- **THEN** return JWT token
```

**WRONG** (don't use bullets or bold):
```markdown
- **Scenario: User login**  ❌
**Scenario**: User login     ❌
### Scenario: User login      ❌
```

Every requirement MUST have at least one scenario.

### Requirement Wording
- Use SHALL/MUST for normative requirements (avoid should/may unless intentionally non-normative)

### Delta Operations

- `## ADDED Requirements` - New capabilities
- `## MODIFIED Requirements` - Changed behavior
- `## REMOVED Requirements` - Deprecated features
- `## RENAMED Requirements` - Name changes

Headers matched with `trim(header)` - whitespace ignored.

#### When to use ADDED vs MODIFIED

**ADDED** - Use when:
- Introducing a new capability or sub-capability that can stand alone
- Adding orthogonal functionality (e.g., "Slash Command Configuration" alongside existing config)
- Creating a new requirement that doesn't alter existing behavior
- **Example**: Adding FILE field type to type-system capability
  ```markdown
  ## ADDED Requirements
  ### Requirement: File Field Type Support
  The system SHALL support FILE field type...
  ```

**MODIFIED** - Use when:
- Changing the behavior, scope, or acceptance criteria of an existing requirement
- Updating existing requirement text (must include full requirement)
- Altering semantics of existing capability
- **Critical**: Always paste the full, updated requirement content (header + all scenarios)
- **Warning**: Partial deltas will drop previous details at archive time
- **Example**: Updating supported field types list
  ```markdown
  ## MODIFIED Requirements
  ### Requirement: Supported Field Types
  The system SHALL support multiple data types: STRING, INTEGER, DECIMAL, DATE, DATETIME, BOOLEAN, JSON, and FILE.
  [Include all existing scenarios plus new FILE scenario]
  ```

**REMOVED** - Use when:
- Deprecating a feature or requirement
- Removing obsolete functionality
- Must include reason and migration path
- **Example**: Removing legacy shapes support
  ```markdown
  ## REMOVED Requirements
  ### Requirement: Shapes System
  **Reason**: Replaced by Field Sets which provide better instance-level field configuration
  **Migration**: Use `php artisan flexyfield:migrate-shapes` to migrate existing shapes to field sets
  ```

**RENAMED** - Use when:
- Only the name changes (no behavior change)
- If behavior also changes, use RENAMED (name) plus MODIFIED (content) referencing new name
- **Example**: Renaming "Shapes" to "Field Sets"
  ```markdown
  ## RENAMED Requirements
  - FROM: `### Requirement: Shapes System`
  - TO: `### Requirement: Field Sets System`
  ```

Common pitfall: Using MODIFIED to add a new concern without including the previous text. This causes loss of detail at archive time. If you aren’t explicitly changing the existing requirement, add a new requirement under ADDED instead.

Authoring a MODIFIED requirement correctly:
1) Locate the existing requirement in `openspec/specs/<capability>/spec.md`.
2) Copy the entire requirement block (from `### Requirement: ...` through its scenarios).
3) Paste it under `## MODIFIED Requirements` and edit to reflect the new behavior.
4) Ensure the header text matches exactly (whitespace-insensitive) and keep at least one `#### Scenario:`.

Example for RENAMED:
```markdown
## RENAMED Requirements
- FROM: `### Requirement: Login`
- TO: `### Requirement: User Authentication`
```

## Troubleshooting

### Common Errors and Solutions

**"Change must have at least one delta"**
- **Problem**: No spec delta files found or files don't have operation prefixes
- **Solution**: 
  - Check `changes/[name]/specs/` directory exists with .md files
  - Verify files start with `## ADDED Requirements`, `## MODIFIED Requirements`, etc.
  - Ensure files are in `specs/[capability]/spec.md` structure
- **Debug**: `openspec show [change] --json --deltas-only` to see what's detected

**"Requirement must have at least one scenario"**
- **Problem**: Requirement block doesn't have scenarios or scenarios are malformed
- **Solution**:
  - Every requirement MUST have at least one `#### Scenario:` (4 hashtags, not 3)
  - Don't use bullet points (`- **Scenario:**`) or bold (`**Scenario:**`) for headers
  - Use exact format: `#### Scenario: Descriptive Name`
- **Example of CORRECT format:**
  ```markdown
  ### Requirement: Feature Name
  The system SHALL do something.
  
  #### Scenario: Success case
  - **WHEN** condition
  - **THEN** outcome
  ```

**"Silent scenario parsing failures"**
- **Problem**: Scenarios exist but aren't being parsed
- **Solution**:
  - Exact format required: `#### Scenario: Name` (4 hashtags, colon, space, name)
  - No extra formatting in header line
  - Ensure WHEN/THEN are indented with `- **WHEN**` and `- **THEN**`
- **Debug**: `openspec show [change] --json --deltas-only | jq '.deltas[].requirements[].scenarios'`

**"Requirement header mismatch"**
- **Problem**: MODIFIED requirement header doesn't match existing requirement
- **Solution**:
  - Copy exact header text from `openspec/specs/<capability>/spec.md`
  - Header matching is whitespace-insensitive but text must match exactly
  - Use `openspec show <spec> --type spec` to see current requirement headers

**"Delta operation not recognized"**
- **Problem**: Operation header not in expected format
- **Solution**:
  - Must be exactly: `## ADDED Requirements`, `## MODIFIED Requirements`, `## REMOVED Requirements`, or `## RENAMED Requirements`
  - Case-sensitive, must use plural "Requirements"
  - No extra text or formatting

### Common AI Assistant Pitfalls

**Pitfall 1: Using MODIFIED to add new concerns**
- **Problem**: Adding new functionality under MODIFIED without including previous requirement text
- **Solution**: Use ADDED for new orthogonal capabilities, or include full previous requirement text in MODIFIED
- **Example**: Adding "Slash Command Configuration" should be ADDED, not MODIFIED to "Configuration"

**Pitfall 2: Partial requirement updates**
- **Problem**: Only updating part of a requirement in MODIFIED, causing loss of detail
- **Solution**: Always copy entire requirement block (header + all scenarios) before modifying
- **Process**: 
  1. Read `openspec/specs/<capability>/spec.md`
  2. Copy full requirement block
  3. Paste under `## MODIFIED Requirements`
  4. Edit to reflect changes
  5. Ensure at least one scenario remains

**Pitfall 3: Incorrect scenario formatting**
- **Problem**: Using wrong header level or format for scenarios
- **Solution**: 
  - Always use `#### Scenario:` (4 hashtags)
  - Never use `### Scenario:` (3 hashtags) or `- **Scenario:**` (bullet)
  - Always include WHEN and THEN clauses

**Pitfall 4: Missing validation**
- **Problem**: Not running validation before sharing proposal
- **Solution**: Always run `openspec validate [change-id] --strict` before requesting approval
- **Check**: All errors must be resolved before implementation

**Pitfall 5: Creating duplicate capabilities**
- **Problem**: Creating new capability when existing one should be modified
- **Solution**: 
  - Always check `openspec list --specs` first
  - Use `openspec show <spec>` to review existing capabilities
  - Prefer MODIFIED over ADDED when extending existing capability

### Validation Tips

```bash
# Always use strict mode for comprehensive checks
openspec validate [change] --strict

# Debug delta parsing
openspec show [change] --json | jq '.deltas'

# Check specific requirement
openspec show [spec] --json -r 1
```

## Happy Path Script

```bash
# 1) Explore current state
openspec spec list --long
openspec list
# Optional full-text search:
# rg -n "Requirement:|Scenario:" openspec/specs
# rg -n "^#|Requirement:" openspec/changes

# 2) Choose change id and scaffold
CHANGE=add-two-factor-auth
mkdir -p openspec/changes/$CHANGE/{specs/auth}
printf "## Why\n...\n\n## What Changes\n- ...\n\n## Impact\n- ...\n" > openspec/changes/$CHANGE/proposal.md
printf "## 1. Implementation\n- [ ] 1.1 ...\n" > openspec/changes/$CHANGE/tasks.md

# 3) Add deltas (example)
cat > openspec/changes/$CHANGE/specs/auth/spec.md << 'EOF'
## ADDED Requirements
### Requirement: Two-Factor Authentication
Users MUST provide a second factor during login.

#### Scenario: OTP required
- **WHEN** valid credentials are provided
- **THEN** an OTP challenge is required
EOF

# 4) Validate
openspec validate $CHANGE --strict
```

## Multi-Capability Example

```
openspec/changes/add-2fa-notify/
├── proposal.md
├── tasks.md
└── specs/
    ├── auth/
    │   └── spec.md   # ADDED: Two-Factor Authentication
    └── notifications/
        └── spec.md   # ADDED: OTP email notification
```

auth/spec.md
```markdown
## ADDED Requirements
### Requirement: Two-Factor Authentication
...
```

notifications/spec.md
```markdown
## ADDED Requirements
### Requirement: OTP Email Notification
...
```

## Best Practices

### Simplicity First
- Default to <100 lines of new code
- Single-file implementations until proven insufficient
- Avoid frameworks without clear justification
- Choose boring, proven patterns

### Complexity Triggers
Only add complexity with:
- Performance data showing current solution too slow
- Concrete scale requirements (>1000 users, >100MB data)
- Multiple proven use cases requiring abstraction

### Clear References
- Use `file.ts:42` format for code locations
- Reference specs as `specs/auth/spec.md`
- Link related changes and PRs

### Capability Naming
- **Format**: Use verb-noun pattern in kebab-case
- **Examples**: 
  - ✅ `user-auth` (authentication capability)
  - ✅ `payment-capture` (payment processing)
  - ✅ `field-validation` (field validation rules)
  - ✅ `dynamic-field-storage` (EAV storage)
  - ❌ `auth` (too vague)
  - ❌ `user-authentication-and-authorization` (needs "AND" - split into two)
- **Rules**:
  - Single purpose per capability (10-minute understandability rule)
  - Split if description needs "AND" (indicates multiple concerns)
  - Be specific but concise
  - Check existing capabilities: `openspec list --specs`

**Current FlexyField capabilities:**
- `documentation` - Documentation requirements
- `dynamic-field-storage` - EAV storage implementation
- `field-set-management` - Field set CRUD operations
- `field-validation` - Validation rules and enforcement
- `query-integration` - Query builder integration
- `testing` - Testing requirements and strategies
- `type-system` - Field type definitions and storage

### Change ID Naming
- **Format**: kebab-case, verb-led, short and descriptive
- **Prefixes**: Prefer verb-led prefixes: `add-`, `update-`, `remove-`, `refactor-`, `improve-`, `fix-`
- **Examples**:
  - ✅ `add-file-field-type` (adds new FILE type)
  - ✅ `update-validation-rules` (updates validation)
  - ✅ `remove-legacy-shapes` (removes deprecated feature)
  - ✅ `refactor-view-recreation` (refactors existing code)
  - ✅ `improve-documentation-for-ai-and-developers` (improves docs)
  - ❌ `file-type` (missing verb prefix)
  - ❌ `add-file-field-type-support` (too verbose)
  - ❌ `new_feature` (wrong format, use kebab-case)
- **Uniqueness**: 
  - Check existing: `openspec list`
  - If taken, append `-2`, `-3`, etc.: `add-feature-2`
- **Best Practices**:
  - Be specific about what's changing
  - Keep under 50 characters when possible
  - Use present tense verbs (add, not adding)

## Tool Selection Guide

| Task | Tool | Why |
|------|------|-----|
| Find files by pattern | Glob | Fast pattern matching |
| Search code content | Grep | Optimized regex search |
| Read specific files | Read | Direct file access |
| Explore unknown scope | Task | Multi-step investigation |

## Error Recovery

### Change Conflicts
1. Run `openspec list` to see active changes
2. Check for overlapping specs
3. Coordinate with change owners
4. Consider combining proposals

### Validation Failures
1. Run with `--strict` flag
2. Check JSON output for details
3. Verify spec file format
4. Ensure scenarios properly formatted

### Missing Context
1. Read project.md first
2. Check related specs
3. Review recent archives
4. Ask for clarification

## Quick Reference

### Stage Indicators
- `changes/` - Proposed, not yet built
- `specs/` - Built and deployed
- `archive/` - Completed changes

### File Purposes
- `proposal.md` - Why and what
- `tasks.md` - Implementation steps
- `design.md` - Technical decisions
- `spec.md` - Requirements and behavior
- `resources/boost/guidelines/core.blade.php` - Laravel Boost AI guidance documentation

### Laravel Boost Integration

Laravel Boost is an MCP (Model Context Protocol) server that accelerates AI-assisted development by providing essential context and structure for generating high-quality, Laravel-specific code. The `resources/boost/guidelines/core.blade.php` file serves as the primary AI guidance document for FlexyField.

#### Purpose of core.blade.php
The `core.blade.php` file contains structured documentation that:
- Provides AI assistants with accurate information about FlexyField's features and usage patterns
- Includes code examples with proper syntax highlighting using `@verbatim` and `@endverbatim` tags
- Follows Laravel Boost's format guidelines for optimal AI comprehension
- Serves as the single source of truth for AI-generated FlexyField code

#### How AI Assistants Use core.blade.php
When an AI assistant is working with FlexyField:
1. Laravel Boost loads the `core.blade.php` file as context
2. The AI uses this information to generate accurate, framework-appropriate code
3. Code examples in the file provide templates for common use cases
4. The AI follows the documented patterns and best practices

#### Maintaining core.blade.php
- Every proposal must include updates to this file when adding new features
- Use the established format with `@verbatim` and `@endverbatim` tags for code examples
- Include practical examples that demonstrate real-world usage
- Follow the existing structure and formatting for consistency

#### Example Format
```blade
@verbatim
<code-snippet name="Descriptive Name" lang="php">
// Code example here
</code-snippet>
@endverbatim
```

### CLI Essentials
```bash
openspec list              # What's in progress?
openspec show [item]       # View details
openspec validate --strict # Is it correct?
openspec archive <change-id> [--yes|-y]  # Mark complete (add --yes for automation)
```

## Quick Reference for AI Assistants

### When to Create a Proposal
- ✅ New feature or capability
- ✅ Breaking changes (API, schema)
- ✅ Architecture changes
- ✅ Performance optimizations that change behavior
- ✅ Security pattern updates
- ❌ Bug fixes (restore intended behavior)
- ❌ Typos, formatting, comments
- ❌ Non-breaking dependency updates
- ❌ Configuration changes
- ❌ Tests for existing behavior

### Mandatory Requirements for All Proposals
- **Testing**: Every proposal must include comprehensive testing requirements in tasks.md
  - Unit tests for new functionality
  - Integration tests for component interactions
  - Feature tests for user workflows
  - Updates to existing tests when behavior changes
  
- **Documentation**: Every proposal must include documentation updates in tasks.md
  - Update README.md with new feature documentation
  - Update Laravel Boost core.blade.php with AI guidance for the new feature
  - Add code examples to documentation
  - Update changelog with breaking changes and new features

### Proposal Creation Checklist
- [ ] Read `openspec/project.md` for context
- [ ] Run `openspec list` and `openspec list --specs` to check existing work
- [ ] Choose unique verb-led change-id (kebab-case)
- [ ] Create `proposal.md` with Why/What/Impact
- [ ] Create `tasks.md` with implementation checklist
- [ ] Create `design.md` only if needed (cross-cutting, new patterns, security)
- [ ] Create spec deltas in `specs/[capability]/spec.md` for each affected capability
- [ ] Include testing requirements in tasks.md for all new functionality
- [ ] Include documentation updates in tasks.md (README.md and Laravel Boost core.blade.php)
- [ ] Run `openspec validate [change-id] --strict`
- [ ] Fix all validation errors
- [ ] Request approval before implementation

### Spec Delta Format Quick Reference

**ADDED** (new capability):
```markdown
## ADDED Requirements
### Requirement: Feature Name
Description of what the system SHALL do.

#### Scenario: Case name
- **WHEN** condition
- **THEN** outcome
```

**MODIFIED** (change existing):
```markdown
## MODIFIED Requirements
### Requirement: Existing Feature Name
[Full updated requirement text - copy from spec and edit]

#### Scenario: Updated case
- **WHEN** updated condition
- **THEN** updated outcome
```

**REMOVED** (deprecate):
```markdown
## REMOVED Requirements
### Requirement: Old Feature Name
**Reason**: Why removing
**Migration**: How to handle
```

**RENAMED** (name change only):
```markdown
## RENAMED Requirements
- FROM: `### Requirement: Old Name`
- TO: `### Requirement: New Name`
```

### Validation Commands
```bash
# Comprehensive validation
openspec validate [change-id] --strict

# Debug delta parsing
openspec show [change-id] --json --deltas-only | jq '.deltas'

# Check specific requirement
openspec show [spec] --type spec --json | jq '.requirements[] | select(.header == "Requirement: Name")'
```

Remember: Specs are truth. Changes are proposals. Keep them in sync.
