# 🏈 Sunday-Squares

A sleek, mobile-friendly, fully customizable, mobile-friendly Super Bowl Squares game built with PHP and SQLite.

## Features

- **Beautiful dark theme** with gold accents
- **Mobile-responsive** grid that works on any device
- **Easy square selection** — tap to select multiple squares, purchase in one transaction
- **Admin dashboard** for the commissioner:
  - Update scores each quarter
  - Confirm Venmo payments
  - Randomize numbers before kickoff
  - Configure teams, prices, and Venmo handle
- **Auto-calculated prizes** — set price per square, prizes auto-divide
- **Live winner detection** — winning squares highlight with trophy icons
- **Leaderboard** — tracks total winnings per player

## How Super Bowl Squares Works

1. **Buy squares** ($10 each by default) — players select empty squares and send payment via Venmo
2. **Commissioner confirms payments** — squares lock in once paid
3. **Numbers randomized** — before kickoff, numbers 0-9 are randomly assigned to rows and columns
4. **Win money!** — at the end of each quarter, the last digit of each team's score determines the winning square

**Example:** If the score is Chiefs 17, Eagles 14 at halftime, find where column **7** meets row **4** — that person wins!

## Installation

1. Upload these files to any PHP web hosting:
   - `index.php` — public game board
   - `admin.php` — commissioner dashboard
   - `squares.db` — SQLite database

2. Make sure the directory is writable (for the SQLite database)

3. Visit `yoursite.com/admin.php` to configure your game
   - Default password: `arnold` (change this in the database!)

## Configuration

All settings are configurable in the Admin dashboard:

| Setting | Description |
|---------|-------------|
| Game Name | Displayed as the title |
| Top Team | Team on the columns (X-axis) |
| Side Team | Team on the rows (Y-axis) |
| Price Per Square | Cost per square (prizes auto-calculate) |
| Venmo Handle | Your Venmo username for payments |

## File Structure

```
squares/
├── index.php      # Public game board
├── admin.php      # Commissioner dashboard  
├── squares.db     # SQLite database
└── README.md      # This file
```

## Tech Stack

- **Backend:** PHP 8.0+
- **Database:** SQLite 3
- **Frontend:** Vanilla HTML/CSS/JavaScript
- **Fonts:** Bebas Neue, Inter (Google Fonts)
  
![sunday_squares_main](https://github.com/user-attachments/assets/9c8a26b6-ae1e-492c-8b3c-e410a342dd43)
![sunday_squares_admin1](https://github.com/user-attachments/assets/9147670c-1c3e-4462-9ba5-1f70a9de48f8)
![sunday_squares_admin2](https://github.com/user-attachments/assets/7f4b90df-d9c1-4faa-9c22-b8ef73ec2bad)

## License

MIT License — feel free to use for your own Super Bowl party!
---

Built with ❤️ and 🏈 by [PIBER](https://github.com/PIBER)
