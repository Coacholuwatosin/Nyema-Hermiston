# Nyema Hermiston — Professional Website

**URL:** nyemahermiston.com.au  
**Stack:** HTML5, CSS3, Vanilla JavaScript, Bootstrap 5, AOS (Animate On Scroll)

## Pages
- `index.html` — Homepage (all 11 sections)
- `about.html` — Full biography, credentials, career timeline
- `books.html` — Individual entries for all 4 books
- `mediaPress.html` — Press kit, author bio for press, media contact
- `speaking.html` — Topics, audiences, speaking enquiry form
- `contact.html` — Full standalone contact page

## Assets

### CSS (in `assets/css/`)
| File | Purpose |
|------|---------|
| `main.css` | CSS variables, reset, global utilities, buttons, cursor, loader |
| `nav.css` | Fixed navigation bar + mobile overlay |
| `hero.css` | Homepage hero section |
| `trustBar.css` | Scrolling trust indicators marquee |
| `about.css` | About preview (homepage) + full About page |
| `books.css` | Books showcase grid + individual book entries |
| `endorsements.css` | Professional endorsement cards |
| `reviews.css` | Reader reviews carousel |
| `research.css` | Research section with watermark |
| `newsletter.css` | Newsletter signup section |
| `contact.css` | Contact form and section |
| `footer.css` | Site footer |
| `responsive.css` | All media query overrides (mobile-first) |

### JavaScript (in `assets/js/`)
| File | Purpose |
|------|---------|
| `main.js` | Loader, scroll bar, cursor, AOS init, mobile nav, newsletter |
| `navScroll.js` | Nav background transition on scroll |
| `trustBarScroll.js` | Trust bar clone for seamless loop |
| `reviewsCarousel.js` | Reviews carousel with touch/swipe support |
| `booksCarousel.js` | 3D tilt effect on book cards |
| `formHandler.js` | Contact form validation and submit |

### Images (in `assets/images/`) — Add these files:
- `nyemaHeadshot.jpg` — Professional author photo
- `bookCoverAutism.jpg` — Planning Parenthood in the Age of Autism
- `bookCoverEarInfections.jpg` — Treat Your Child's Ear Infections Yourself
- `bookCoverTreatChild.jpg` — Treat Your Child Yourself
- `bookCoverGoodNews.jpg` — Good News for People with Bad News
- `aurumProjectLogo.png` — The Aurum Project logo

## Naming Conventions
- CSS classes: `camelCase`
- JavaScript variables: `camelCase`
- File names: `camelCase`
- No inline CSS (zero exceptions)
- All font sizes use `clamp()` for fluid scaling
- All spacing uses CSS custom properties only

## To Launch
1. Add image files to `assets/images/`
2. Insert GA4 tracking code where marked `<!-- GA4 TRACKING CODE HERE -->`
3. Wire contact and newsletter forms to your backend/service (Formspree, Netlify Forms, etc.)
4. Deploy to nyemahermiston.com.au

## Design System
Colors, typography, and spacing are all defined as CSS custom properties in `assets/css/main.css` `:root` block.
