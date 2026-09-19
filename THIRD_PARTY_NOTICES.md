# Third-party notices and asset provenance

This inventory is a preservation aid, not a legal clearance report. The copied `public/dashboard` tree came from the host application. No upstream purchase record, theme license certificate, or complete dependency lockfile was found beside it during packaging. **Private/internal distribution is required until rights are verified.**

Detected headers or recognizable vendored paths include:

| Component | Evidence in package | License indicated by upstream header/path |
|---|---|---|
| Bootstrap 4.3.1 | `public/dashboard/auth/js/bootstrap.min.js`, auth SCSS | MIT header preserved |
| Bootstrap 5.3.5 | `public/dashboard/vendor/css/core.css` | MIT header preserved |
| jQuery | `public/dashboard/vendor/libs/jquery`, auth bundle | Common upstream distribution; verify exact version/license file |
| Popper | `public/dashboard/vendor/libs/popper`, auth bundle | Common upstream distribution; verify exact version/license file |
| Select2 and translations | `public/dashboard/vendor/libs/select2`, `public/dashboard/js/select2` | Common upstream distribution; verify exact version/license file |
| DataTables | `public/dashboard/vendor/libs/datatables-*` | Common upstream distribution; verify exact edition/license |
| ApexCharts, Quill, Dropzone, SweetAlert2, Leaflet, Moment, Swiper, Notyf and other plugins | corresponding `public/dashboard/vendor/libs/*` paths | Version/license files were not consistently present; verify each before redistribution |
| Dashboard template/theme layer, illustrations, icons, fonts, demo images | `public/dashboard/vendor/css`, `img`, `fonts`, `js` | **Unknown commercial/theme license. Not cleared for public redistribution.** |

Copyright and license comments embedded in minified or source files are intentionally retained. Do not strip them when rebuilding or publishing. Before any external distribution, replace this inventory with a file-by-file software-bill-of-materials and attach the applicable license texts and commercial purchase evidence.
