# Ideas

Ideas outside the current scope. They are not planned work.

- Add a Vue template accessibility lint, for example `eslint-plugin-vuejs-accessibility`, if the checks in stage 7 show a gap.
- Add database indexes for sorting by votes and date once a larger data set shows they are needed.
- Combine full-text search with `pg_trgm` to also find typos and parts of compound words.
- Select only the needed columns in lists, so the `tsvector` column is not loaded.
- Ask for confirmation before the final statuses `done` and `declined`.
- Limit the `done` column of the roadmap for products with many finished requests.
- Test the app with a real screen reader.
- Add security headers such as a Content Security Policy.
