import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Team from '../../common/models/Team';
import PickemEvent from '../../common/models/Event';
import Pick from '../../common/models/Pick';
import dayjs from 'dayjs';

interface EventCardAttrs {
  event: PickemEvent;
  pick?: Pick;
  onMakePick: (eventId: number, outcome: string) => Promise<void>;
  isLoading: boolean;
}

export default class EventCard extends Component<EventCardAttrs> {
  view() {
    const { event, pick, onMakePick, isLoading } = this.attrs;

    if (!event || typeof event.id !== 'function') {
      return null;
    }

    const homeTeam = event.homeTeam() as Team | false;
    const awayTeam = event.awayTeam() as Team | false;
    const canPick = event.canPick();
    const status = event.status();
    const result = event.result();
    const homeScore = event.homeScore();
    const awayScore = event.awayScore();

    const reverse = app.forum.attribute<boolean>('bragalotto.reverseDisplay');

    let matchDate = '-';
    let cutoffDate = '-';
    try {
      matchDate = dayjs(event.matchDate()).format('DD MMM YYYY, HH:mm');
    } catch {
      matchDate = String(event.matchDate());
    }
    try {
      cutoffDate = dayjs(event.cutoffDate()).format('DD MMM YYYY, HH:mm');
    } catch {
      cutoffDate = String(event.cutoffDate());
    }

    const cutoffDateVal = event.cutoffDate();
    const countdown = cutoffDateVal ? this.getCountdown(cutoffDateVal) : null;

    const firstTeam = reverse ? awayTeam : homeTeam;
    const secondTeam = reverse ? homeTeam : awayTeam;
    const firstTeamLabel = reverse ? app.translator.trans('bigreja-bragalotto.lib.common.away') : app.translator.trans('bigreja-bragalotto.lib.common.home');
    const secondTeamLabel = reverse ? app.translator.trans('bigreja-bragalotto.lib.common.home') : app.translator.trans('bigreja-bragalotto.lib.common.away');
    
    const firstScore = reverse ? awayScore : homeScore;
    const secondScore = reverse ? homeScore : awayScore;

    const firstOutcome = reverse ? 'away' : 'home';
    const secondOutcome = reverse ? 'home' : 'away';

    return (
      <div className="EventCard">
        <div className={`EventCard-status ${status}`}>
          {app.translator.trans(`bigreja-bragalotto.lib.status.${status}`)}
        </div>

        <div className="EventCard-teams">
          <div className="team-container">
            {this.renderTeamLogo(firstTeam)}
            <div className="team-name">{firstTeam ? firstTeam.name() : firstTeamLabel}</div>
          </div>

          <div className="vs">{app.translator.trans('bigreja-bragalotto.lib.common.vs')}</div>

          <div className="team-container">
            {this.renderTeamLogo(secondTeam)}
            <div className="team-name">{secondTeam ? secondTeam.name() : secondTeamLabel}</div>
          </div>
        </div>

        {status === 'finished' && homeScore !== null && awayScore !== null && (
          <div className="EventCard-score">
            <div className="score-number">{firstScore}</div>
            <div className="score-separator">-</div>
            <div className="score-number">{secondScore}</div>
          </div>
        )}

        <div className="EventCard-info">
          <div>
            <i className="fas fa-calendar" />
            <strong>{app.translator.trans('bigreja-bragalotto.lib.headers.match_date')}:</strong> {matchDate}
          </div>
          <div>
            <i className="fas fa-clock" />
            <strong>{app.translator.trans('bigreja-bragalotto.lib.headers.cutoff_date')}:</strong> {cutoffDate}
          </div>
          {countdown && canPick && (
            <div>
              <span className={`EventCard-countdown ${countdown.urgent ? 'urgent' : ''}`}>
                <i className="fas fa-hourglass-half" />
                {countdown.text}
              </span>
            </div>
          )}
          {result && (
            <div>
              <i className="fas fa-flag-checkered" />
              <strong>{app.translator.trans('bigreja-bragalotto.lib.common.result')}:</strong> {this.formatResult(result, homeTeam, awayTeam)}
            </div>
          )}
        </div>

        {app.session.user && canPick && (
          <div className="EventCard-picks">
            <Button
              className={`Button ${pick && pick.selectedOutcome() === firstOutcome ? 'Button--pickem-selected' : ''}`}
              onclick={() => onMakePick(Number(event.id()), firstOutcome)}
              loading={isLoading}
              disabled={isLoading}
            >
              {firstTeam ? firstTeam.name() : firstTeamLabel}
            </Button>

            {event.allowDraw() && (
              <Button
                className={`Button ${pick && pick.selectedOutcome() === 'draw' ? 'Button--pickem-selected' : ''}`}
                onclick={() => onMakePick(Number(event.id()), 'draw')}
                loading={isLoading}
                disabled={isLoading}
              >
                {app.translator.trans('bigreja-bragalotto.lib.common.draw')}
              </Button>
            )}

            <Button
              className={`Button ${pick && pick.selectedOutcome() === secondOutcome ? 'Button--pickem-selected' : ''}`}
              onclick={() => onMakePick(Number(event.id()), secondOutcome)}
              loading={isLoading}
              disabled={isLoading}
            >
              {secondTeam ? secondTeam.name() : secondTeamLabel}
            </Button>
          </div>
        )}

        {pick && !canPick && (
          <div className="EventCard-pick-result">
            {app.translator.trans('bigreja-bragalotto.forum.picks.your_pick')}: <strong>{this.formatResult(pick.selectedOutcome(), homeTeam, awayTeam)}</strong>
            {pick.isCorrect() !== null && (
              <span className={pick.isCorrect() ? 'correct' : 'incorrect'}>
                {pick.isCorrect() 
                  ? ` ✓ ${app.translator.trans('bigreja-bragalotto.lib.status.correct')}` 
                  : ` ✗ ${app.translator.trans('bigreja-bragalotto.lib.status.incorrect')}`}
              </span>
            )}
          </div>
        )}
      </div>
    );
  }

  renderTeamLogo(team: Team | false | null) {
    if (!team) {
      return (
        <div className="team-logo">
          <span>?</span>
        </div>
      );
    }

    const logoUrl = team.logoUrl();
    const teamName = team.name() || app.translator.trans('core.lib.username.deleted_text');

    if (logoUrl) {
      return (
        <div className="team-logo">
          <img src={logoUrl} alt={String(teamName)} />
        </div>
      );
    }
    
    const nameStr = String(teamName);
    const initial = nameStr.charAt(0).toUpperCase();
    const colors = ['#e74c3c', '#3498db', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#e67e22', '#34495e'];
    const colorIndex = nameStr.charCodeAt(0) % colors.length;
    const bgColor = colors[colorIndex];

    return (
      <div className="team-logo" style={`background-color: ${bgColor};`}>
        <span>{initial}</span>
      </div>
    );
  }

  getCountdown(cutoffDate: Date) {
    try {
      const now = dayjs();
      const cutoff = dayjs(cutoffDate);
      const diff = cutoff.diff(now);

      if (diff <= 0) return null;

      const hours = Math.floor(diff / (1000 * 60 * 60));
      const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

      if (hours < 1) {
        return {
          text: app.translator.trans('bigreja-bragalotto.lib.time.minutes_remaining', { minutes }),
          urgent: minutes < 30,
        };
      }

      if (hours < 24) {
        return {
          text: app.translator.trans('bigreja-bragalotto.lib.time.hours_remaining', { hours, minutes }),
          urgent: hours < 2,
        };
      }

      const days = Math.floor(hours / 24);
      return {
        text: app.translator.trans('bigreja-bragalotto.lib.time.days_remaining', { days }),
        urgent: false,
      };
    } catch {
      return null;
    }
  }

  formatResult(result: string | null, homeTeam: Team | false | null, awayTeam: Team | false | null) {
    if (result === 'home') return homeTeam ? homeTeam.name() : app.translator.trans('bigreja-bragalotto.lib.common.home');
    if (result === 'away') return awayTeam ? awayTeam.name() : app.translator.trans('bigreja-bragalotto.lib.common.away');
    if (result === 'draw') return app.translator.trans('bigreja-bragalotto.lib.common.draw');
    return result;
  }
}