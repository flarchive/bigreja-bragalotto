# Liga Portugal API Reverse Engineering

## Overview

Base URLs:

```text
https://www.ligaportugal.pt/api/v1/
https://www.ligaportugal.pt/api/v2/
```

IMPORTANT

Endpoints are confirmed from Liga Portugal frontend JavaScript bundles.

Response bodies are inferred from frontend consumption and property usage.

- Endpoint URLs are HIGH CONFIDENCE.
- Response schemas are MEDIUM CONFIDENCE until validated against live responses.
- Treat all fields as optional.
- Preserve unknown fields.

---

# API Endpoints and Sample Responses

## GET /api/v2/seasons

Sample response:

```json
[
  {
    "id": 20262027,
    "label": "2026/2027",
    "current": true,
    "competitions": [
      {
        "id": 1,
        "externalId": "ligaportugalbetclic",
        "label": "Liga Portugal Betclic"
      }
    ]
  }
]
```

## GET /api/v1/competition/season/rounds

```json
[
  {
    "id": 1,
    "label": "Jornada 1"
  }
]
```

## GET /api/v1/competition/matches

```json
[
  {
    "id": 12345,
    "status": "NOT_STARTED",
    "homeTeam": {"id": 3, "name": "Sporting CP"},
    "awayTeam": {"id": 5, "name": "FC Porto"}
  }
]
```

## GET /api/v2/competition/standings

```json
{
  "lastRound": "1",
  "standings": [
    {
      "key": "Overall",
      "standings": [
        {
          "position": 1,
          "points": 3,
          "matches": 1,
          "wins": 1,
          "draws": 0,
          "loses": 0,
          "goalsScored": 2,
          "goalsConceded": 0,
          "recent_form": "V"
        }
      ]
    }
  ]
}
```

## Confirmed Endpoint Inventory

### Competition

```text
/api/v1/competition/{id}
/api/v1/competition/matches
/api/v1/competition/season/rounds
/api/v1/competition/teams
/api/v1/competition/matchweek/{round}/statistics
/api/v1/competition/round/{round}/nominations
/api/v1/competition/pitch/ratings
/api/v1/competition/stadiums/rating
/api/v1/competition/spectators/statistics
/api/v1/competition/usefultime
```

### Match

```text
/api/v1/match/details
/api/v1/match/info/static
/api/v1/match/formations
/api/v1/match/mvp
/api/v1/match/climate
/api/v1/match/teams/history
/api/v2/match/info/dynamic
```

### Teams

```text
/api/v1/team/info
/api/v1/team/squad
/api/v1/team/matches
/api/v1/team/statistics
/api/v1/team/standings
/api/v1/team/{id}/stadium
/api/v2/team/leaders
```

### Players

```text
/api/v1/player/{id}
/api/v1/player/{id}/history
/api/v1/player/{id}/matches
/api/v1/player/{id}/videos
/api/v1/player/{id}/season/statistics
/api/v1/player/{id}/competition/statistics
```

### Staff

```text
/api/v1/coach/{id}
/api/v1/referee/{id}
/api/v1/delegate/{id}
/api/v1/observer/{id}
```

### Search

```text
/api/v1/search/news/{term}
/api/v1/search/videos/{term}
```

### International

```text
/api/v2/international/seasons
/api/v2/international/competition/{id}/rounds
/api/v2/international/competition/{id}/standings
/api/v2/international/competition/{id}/stages/{stage}/rounds/{round}/matches
```

### Stats

```text
/api/v2/competition/top/players
/api/v2/team/leaders
```
