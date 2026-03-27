# Git Workflow Guide for TrustShield AI

## Daily Development Workflow

### 1. Make Changes in Windsurf
- Edit files in Windsurf
- Test your changes locally
- Save your files

### 2. Check Git Status
```bash
git status
```
This shows which files have been modified.

### 3. Stage Your Changes
**Option A: Stage specific files**
```bash
git add path/to/file.php
git add path/to/another-file.css
```

**Option B: Stage all changes**
```bash
git add .
```

### 4. Commit Your Changes
```bash
git commit -m "Descriptive message about what you changed"
```

**Good commit messages:**
- "Add user authentication feature"
- "Fix CSS alignment issues in hero section"
- "Update VPN service API endpoint"

**Bad commit messages:**
- "updates"
- "fix"
- "stuff"

### 5. Push to GitHub
```bash
git push origin main
```

## Using Windsurf's Git Interface

### Visual Method (Recommended)
1. **Open Source Control Panel**: Click the branch icon in the sidebar
2. **See Changes**: Modified files appear under "Changes"
3. **Stage Changes**: Click the + icon next to files you want to commit
4. **Commit Message**: Type your message in the input box
5. **Commit**: Click the checkmark icon
6. **Push**: Click the sync/cloud icon to push to GitHub

### Keyboard Shortcuts
- `Ctrl+Shift+G`: Show/Hide Git panel
- `Ctrl+Enter`: Commit (when in commit message box)

## Common Git Commands

### Check Status
```bash
git status                    # See current state
git log --oneline            # See commit history
```

### Working with Changes
```bash
git diff                     # See unstaged changes
git diff --staged           # See staged changes
```

### Undo Changes
```bash
git checkout -- file.php    # Undo changes to a file
git reset HEAD file.php     # Unstage a file
```

### Branching (Advanced)
```bash
git branch feature-name     # Create new branch
git checkout feature-name   # Switch to branch
git merge feature-name      # Merge branch into main
```

## Best Practices

### 1. Commit Often
- Commit small, logical changes
- One feature per commit
- Fix bugs in separate commits

### 2. Write Good Commit Messages
- Start with a verb (Add, Fix, Update, Remove)
- Be specific about what changed
- Keep under 72 characters

### 3. Push Regularly
- Push at the end of each work session
- Push before switching features
- This creates backups and allows collaboration

### 4. Pull Before Working
```bash
git pull origin main       # Get latest changes from GitHub
```

## Troubleshooting

### "Push rejected" Error
```bash
git pull origin main       # Pull first
git push origin main       # Then push
```

### "Merge conflicts"
1. Open the conflicted files
2. Look for `<<<<<<<`, `=======`, `>>>>>>>` markers
3. Edit to keep what you want
4. Remove the markers
5. Commit the resolution

### Need to undo last commit?
```bash
git reset --soft HEAD~1    # Undo commit but keep changes
git reset --hard HEAD~1    # Undo commit and discard changes
```

## Quick Reference

| Action | Command | Windsurf UI |
|--------|---------|-------------|
| Check status | `git status` | Source Control panel |
| Stage file | `git add file` | Click + next to file |
| Commit | `git commit -m "msg"` | Type message + click ✓ |
| Push | `git push` | Click sync icon |
| Pull | `git pull` | Click sync icon |
| View history | `git log` | Click clock icon |

## Your Current Setup

- **Repository**: https://github.com/Chuks400/cybte.com.git
- **Local Path**: `c:\xampp\htdocs\trustshield-ai`
- **Branch**: `main`
- **Remote**: `origin`

## Example Workflow

```bash
# 1. Start working
cd c:\xampp\htdocs\trustshield-ai

# 2. Make changes in Windsurf (edit files)

# 3. Check what changed
git status

# 4. Stage your changes
git add .

# 5. Commit with good message
git commit -m "Add responsive design for mobile devices"

# 6. Push to GitHub
git push origin main

# 7. Done! Check your work on GitHub
```

---

**Remember**: Your local changes are NOT on GitHub until you commit AND push them!
