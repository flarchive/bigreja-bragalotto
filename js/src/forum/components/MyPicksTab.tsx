import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Pick from '../../common/models/Pick';
import PickemEvent from '../../common/models/Event';
import Team from '../../common/models/Team';

declare global {
  const dayjs: any;
}

interface MyPicksTabAttrs {
  picks: Record<string, Pick>;
  loading: boolean;
  hasMore: boolean;
  onLoadMore: () => void;
}

export default class MyPicksTab extends Component<MyPicksTabAttrs> {
  view() {
    if (!app.session.user) {
      return (
        <div className="MyPicksPage-empty">
          <p>{app.translator.trans('bigreja-bragalotto.lib.messages.login_required')}</p>
        </div>
      );
    }

    const { picks, loading, hasMore, onLoadMore } = this.attrs;
    const myPicks = Object.values(picks || {}) as Pick[];

    const reverse = app.forum.attribute<boolean>('bragalotto.reverseDisplay');

    if ((!myPicks || myPicks.length === 0) && !loading) {
      return (
        <div className="MyPicksPage-empty">
          <i className="fas fa-clipboard-list" style="font-size: 48px; opacity: 0.3; margin-bottom: 16px;" />
          <p>{app.translator.trans('bigreja-bragalotto.lib.messages.no_data')}</p>
        </div>
      );
    }

    const sortedPicks = myPicks
      .filter((pick) => {
        const ev = pick.event();
        return ev !== false && ev !== null;
      })
      .sort((a, b) => {
        const eventA = a.event() as PickemEvent | false;
        const eventB = b.event() as PickemEvent | false;
        
        if (!eventA || !eventB) return 0;

        const dateA = eventA.matchDate() ? new Date(eventA.matchDate()!).getTime() : 0;
        const dateB = eventB.matchDate() ? new Date(eventB.matchDate()!).getTime() : 0;
        return dateB - dateA;
      });

    return (
      <div>
        <div className="PickemList PickemList--my-picks">
          <div className="PickemList-header">
            <div className="PickemList-cell type-match">{app.translator.trans('bigreja-bragalotto.lib.common.match')}</div>
            <div className="PickemList-cell type-date">{app.translator.trans('bigreja-bragalotto.lib.common.date')}</div>
            <div className="PickemList-cell type-pick">{app.translator.trans('bigreja-bragalotto.forum.picks.your_pick')}</div>
            <div className="PickemList-cell type-result">{app.translator.trans('bigreja-bragalotto.lib.common.result')}</div>
            <div className="PickemList-cell type-status">{app.translator.trans('bigreja-bragalotto.lib.common.status')}</div>
          </div>

          <div className="PickemList-body">
            {sortedPicks.map((pick) => {
              const event = pick.event() as PickemEvent | false;
              if (!event) return null;

              const homeTeam = event.homeTeam() as Team | false;
              const awayTeam = event.awayTeam() as Team | false;
              const pickId = pick.id();
              const isCorrect = pick.isCorrect();

              let itemClass = 'pending';
              if (isCorrect === true) itemClass = 'correct';
              if (isCorrect === false) itemClass = 'incorrect';

              let matchDate = '-';
              try {
                matchDate = dayjs(event.matchDate()).format('DD MMM YYYY');
              } catch {}

              const firstTeamName = reverse 
                ? (awayTeam ? awayTeam.name() : app.translator.trans('bigreja-bragalotto.lib.common.away'))
                : (homeTeam ? homeTeam.name() : app.translator.trans('bigreja-bragalotto.lib.common.home'));
              
              const secondTeamName = reverse 
                ? (homeTeam ? homeTeam.name() : app.translator.trans('bigreja-bragalotto.lib.common.home'))
                : (awayTeam ? awayTeam.name() : app.translator.trans('bigreja-bragalotto.lib.common.away'));

              return (
                <div key={String(pickId)} className={`PickemList-item ${itemClass}`}>
                  
                  <div className="PickemList-cell type-match">
                    <span className="mobile-label">{app.translator.trans('bigreja-bragalotto.lib.common.match')}</span>
                    <span className="value">
                        {firstTeamName}{' '}
                        {app.translator.trans('bigreja-bragalotto.lib.common.vs')}{' '}
                        {secondTeamName}
                    </span>
                  </div>

                  <div className="PickemList-cell type-date">
                    <span className="mobile-label">{app.translator.trans('bigreja-bragalotto.lib.common.date')}</span>
                    <span className="value">{matchDate}</span>
                  </div>

                  <div className="PickemList-cell type-pick">
                    <span className="mobile-label">{app.translator.trans('bigreja-bragalotto.forum.picks.your_pick')}</span>
                    <span className="value">{this.formatResult(pick.selectedOutcome(), homeTeam, awayTeam)}</span>
                  </div>

                  <div className="PickemList-cell type-result">
                    <span className="mobile-label">{app.translator.trans('bigreja-bragalotto.lib.common.result')}</span>
                    <span className="value">{event.result() ? this.formatResult(event.result(), homeTeam, awayTeam) : '-'}</span>
                  </div>

                  <div className="PickemList-cell type-status">
                    <span className="mobile-label">{app.translator.trans('bigreja-bragalotto.lib.common.status')}</span>
                    <span className="value">
                      {isCorrect === null ? (
                        <span className="PickStatus PickStatus--pending">
                          <i className="fas fa-hourglass-half" /> {app.translator.trans('bigreja-bragalotto.lib.status.pending')}
                        </span>
                      ) : isCorrect ? (
                        <span className="PickStatus PickStatus--correct">
                          <i className="fas fa-check" /> {app.translator.trans('bigreja-bragalotto.lib.status.correct')}
                        </span>
                      ) : (
                        <span className="PickStatus PickStatus--incorrect">
                          <i className="fas fa-times" /> {app.translator.trans('bigreja-bragalotto.lib.status.incorrect')}
                        </span>
                      )}
                    </span>
                  </div>

                </div>
              );
            })}
          </div>
        </div>

        {hasMore && (
          <div className="LoadMore">
            <Button
              className="Button Button--primary"
              onclick={onLoadMore}
              loading={loading}
            >
              {app.translator.trans('bigreja-bragalotto.lib.buttons.load_more')}
            </Button>
          </div>
        )}
      </div>
    );
  }

  formatResult(result: string | null, homeTeam: Team | false | null, awayTeam: Team | false | null) {
    if (result === 'home') return homeTeam ? homeTeam.name() : app.translator.trans('bigreja-bragalotto.lib.common.home');
    if (result === 'away') return awayTeam ? awayTeam.name() : app.translator.trans('bigreja-bragalotto.lib.common.away');
    if (result === 'draw') return app.translator.trans('bigreja-bragalotto.lib.common.draw');
    return result;
  }
}