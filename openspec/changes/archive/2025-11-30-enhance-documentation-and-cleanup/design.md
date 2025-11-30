# Design: Enhance Documentation and Laravel Boost Guidelines

## Problem Analysis

**Current State:**
- Documentation is functional but lacks GitHub polish
- Laravel Boost guideline exists (`resources/boost/guidelines/core.blade.php`) but could be more comprehensive
- Legacy files (README-CONTRIBUTE.md, _scratchpad.md) add clutter
- README.md missing GitHub best practices

**Laravel Boost Integration:**
- Laravel Boost is an MCP server for AI-assisted Laravel development
- Uses Blade/Markdown files for AI guidelines
- Current guideline: 242 lines, covers basics well
- Enhancement opportunity: More examples, troubleshooting, performance tips

## Design Decisions

### 1. Legacy Documentation Cleanup

**Decision:** Remove minimal/redundant files
**Rationale:**
- README-CONTRIBUTE.md: 110 bytes, no substantial content
- _scratchpad.md: Likely temporary
- AGENTS.md/CLAUDE.md/CLINE.md: All point to openspec/AGENTS.md

**Approach:** Review and delete if <200 bytes or redundant

### 2. GitHub-Optimized README

**Decision:** Add GitHub best practices
**Rationale:** Professional presentation = better adoption

**Enhancements:**
- Badges: ![Build](https://...), ![Coverage](https://...), etc.
- Hero: Clear 1-sentence value prop
- Comparison table: vs. JSON columns, vs. custom tables
- Performance callout: "98% faster in v2.0"
- Visual structure: Emojis, clear sections

**Structure:**
```markdown
# FlexyField [badges]

> Dynamic fields for Laravel without migrations

## ✨ Features
## 🚀 Why FlexyField?
## 📦 Installation
## 🎯 Quick Start
## 📚 Documentation
## ⚡ Performance
## 🤝 Contributing
```

### 3. Laravel Boost Guideline Enhancement

**Decision:** Expand existing core.blade.php
**Rationale:**
- File already exists and works well
- Just needs more depth for better AI assistance
- Keep @verbatim structure for Boost compatibility

**New Sections:**

**Quick Reference (Top):**
```blade
@verbatim
<code-snippet name="Quick Reference" lang="php">
// 1. Enable on model: use Flexy, implements FlexyModelContract
// 2. Create schema: Product::createSchema('code', 'Label')
// 3. Add fields: Product::addFieldToSchema('code', 'name', TYPE)
// 4. Assign: $model->assignToSchema('code')
// 5. Use: $model->flexy->field = value
// 6. Query: where('flexy_field', value)
</code-snippet>
@endverbatim
```

**Common Mistakes:**
- Forgetting to assign schema
- Using `$model->flexy_field` instead of `$model->flexy->field`
- Setting values before model is saved
- Type mismatches

**Troubleshooting:**
- Schema not Found → assignToSchema()
- FieldNotInSchema → Check available fields
- ValidationException → Check validation rules

**Performance Tips:**
- View recreation is smart (v2.0+)
- Keep 20-50 fields per schema
- Index schema_code column

**Blade Integration:**
```blade
{{-- Display flexy field --}}
{{ $product->flexy->color }}

{{-- Form input --}}
<input name="flexy[color]" value="{{ old('flexy.color', $product->flexy->color) }}">

{{-- Validation errors --}}
@error('flexy.color')
    <span>{{ $message }}</span>
@enderror
```

## Implementation Strategy

### Phase 1: Cleanup (30 min)
1. Delete README-CONTRIBUTE.md
2. Delete _scratchpad.md if empty
3. Review/consolidate agent docs

### Phase 2: README (1-2 hours)
1. Add badges
2. Restructure with hero
3. Add comparison table
4. Visual polish

### Phase 3: Boost Guideline (1-2 hours)
1. Add Quick Reference
2. Add Common Mistakes
3. Add Troubleshooting
4. Add Blade examples
5. Add Performance tips

## Risk Assessment

**Overall Risk: LOW**
- Documentation only
- No code changes
- Easy to revert

**Testing:**
- Verify Blade syntax
- Check README rendering on GitHub
- Validate all links

## Success Criteria

- [ ] Zero broken links
- [ ] README has professional GitHub appearance
- [ ] All badges display correctly
- [ ] Boost guideline has no Blade syntax errors
- [ ] No redundant files remain
- [ ] OpenSpec validation passes

## Dependencies

None - documentation-only changes
