# Database Schema (MySQL)

## users
- id (PK)
- name
- email
- password_hash
- role (admin/client/lead)
- created_at

## leads
- id
- email
- phone
- ip
- source
- verified (boolean)
- created_at

## websites
- id
- user_id
- url
- name
- created_at

## scans
- id
- website_id
- lead_id
- score_overall
- seo_score
- legal_score
- accessibility_score
- performance_score
- security_score
- ux_score
- ai_score
- created_at

## scan_results
- id
- scan_id
- category
- issue_type
- severity
- description
- recommendation

## prototypes
- id
- lead_id
- json_structure
- preview_url
- created_at

## monitoring
- id
- website_id
- status
- response_time
- ssl_status
- checked_at