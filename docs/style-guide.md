# MotoGP Bidding — Style Guide

Visual reference for common interface elements used throughout MotoGP Bidding.

## Design principles

### Prefer application defaults

Common presentation and behaviour should be defined as an application default rather than requiring a class to be added everywhere. Classes should normally identify a reusable component, variation, or exception. Application-specific styles are prefixed with `motogp-` to distinguish them from Pico and other third-party styles.

### Work with Pico

Use Pico's native elements and behaviour wherever they suit the application. Add MotoGP styles where the application needs a deliberate variation, rather than recreating or routinely overriding Pico components.

### Show domain state, not database state

Present information in terms that describe the application to the user. For example, an account should be shown as *Pending approval*, *Active*, or *Disabled* rather than exposing the underlying `approved_at` and `disabled_at` fields as its primary status.

### Keep controls close to what they change

Where a table presents a property and its current value, place the control that changes that property in the same row. Related navigation, such as viewing a statement, belongs at page level rather than alongside a control that changes a value.

### Prefer immediate actions when the result is clear

Simple, reversible controls such as switches should take effect immediately rather than requiring a separate Save action. After the action completes, the displayed value or status should reflect the new state.

### Make unavailable actions understandable

Disable an action when it normally belongs in the current context but cannot presently be performed. Omit an action when it does not apply to the current context at all. Interface restrictions supplement, rather than replace, server-side validation and protection.

### Keep secondary information secondary

Audit, diagnostic, and other infrequently used information may be grouped or compacted so that it remains available without competing visually with the page's primary information and actions. A null value displayed in tabular or detail information is represented by an em dash (`—`).

### Responsive navigation

The header uses primary navigation, branding, and account navigation as separate regions. On narrow screens, primary navigation collapses into a menu while account navigation remains independently accessible. Navigation hierarchy and available actions remain consistent across viewport sizes. Submenus expand vertically within the mobile navigation.

Right-side menus open inward.

## Components

The examples below show the standard presentation and use of common interface components.

### Link button

Use a button link for navigation that represents a prominent next step or task, such as viewing a statement, creating a race, or returning to a key workflow. Use a normal text link for navigation within prose, tables, menus, or where the destination does not need particular emphasis. Button links use the primary outline style so they remain visually distinct from actions that change application state. A lowercase text label is required; an icon may optionally precede the label.

```html
<a href="#" role="button" class="outline motogp-icon-text-button">
    {{> partials/icons/statement }}
    statement
</a>
```

### Action button

Use a primary button for an action that changes application state, such as saving, updating, approving or enabling. A lowercase text label is required. An icon may optionally precede the label.

```html
<button type="submit">enable</button>
```

### Icon action button

Use a square icon button for compact actions where space is limited, particularly repeated actions in table rows. The icon represents the action and the button must have an accessible label. A tooltip may repeat the label to make the action clear to sighted users.

Disable the button when the action normally applies but is currently unavailable, following the unavailable-actions principle above.

```html
<button
    type="button"
    class="motogp-icon-button"
    data-tooltip="Edit"
    aria-label="Edit"
>
    {{> partials/icons/edit }}
</button>
```

### Dialog action buttons

Place dialog actions together with cancel first and the primary action last. Cancel uses the secondary style and the action uses the primary style. Buttons size to their contents rather than having equal widths. Use `type="submit"` when the primary action submits a form, and `type="button"` for actions handled entirely by JavaScript. Labels are lowercase.

```html
<fieldset class="motogp-dialog-actions">
    <button type="button" class="secondary">cancel</button>
    <button type="submit">save</button>
</fieldset>
```

### Form actions

Use a primary button to submit a form. Button labels are lowercase and describe the action, such as `login`, `register`, `save`, or `update`.

Include a cancel action when the user is editing existing information and has a meaningful destination to return to. Place cancel to the left of the primary action. Cancel uses a secondary visual treatment and does not submit the form.

Forms with a single primary action, such as login and registration, generally do not need a cancel button. Buttons size to their contents rather than occupying equal widths. Use Pico's native button styling and layout where possible.

Navigation to related pages, such as registration from the login page, uses ordinary text links beneath the form.

For forms with two actions, the existing `motogp-dialog-actions` layout may be reused. A cancel action that navigates to another page should be a link; a cancel action that closes a dialog should be a button.

```html
<fieldset class="motogp-dialog-actions">
    <a href="/user/account.php" role="button" class="outline">cancel</a>
    <button type="submit">save</button>
</fieldset>
```

A login form generally needs only:

```html
<button type="submit">login</button>
<p>Don't have an account? <a href="/user/register.php">register</a>.</p>
```

### Icon

Use an inline icon to supplement text or represent information where its meaning is clear from context. Icons inherit the surrounding text colour and scale with the surrounding font size, so the same icon can be used naturally in tables, labels, prose and headings. When an icon appears without accompanying text, provide an accessible label unless its meaning is already conveyed elsewhere.

In compact displays such as tables, an icon may replace a simple repeated value such as “Yes”; leave the cell blank when the value does not apply.

```html
<span class="motogp-icon" aria-label="Yes">
    {{> partials/icons/check }}
</span>
```

### Table

Use `motogp-table` for application tables. Cell content is top-aligned so that text, multi-line values, and form controls share a consistent starting point.

```html
<table class="motogp-table">
    ...
</table>
```
