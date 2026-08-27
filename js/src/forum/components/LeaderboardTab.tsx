import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import avatar from 'flarum/common/helpers/avatar';

interface LeaderboardTabAttrs {
  userScores: any[];
  myScore: any;
  hasMore: boolean;
  loading: boolean;
  onLoadMore: () => void;
}

export default class LeaderboardTab extends Component<LeaderboardTabAttrs> {
  view() {
    const { userScores, myScore, hasMore, loading, onLoadMore } = this.attrs;

    const hasData = userScores && userScores.length > 0;

    if (!hasData && !loading) {
       return (
          <div className="LeaderboardTab">
            <p>{app.translator.trans('bigreja-bragalotto.lib.messages.no_data')}</p>
          </div>
        );
    }

    return (
      <div className="LeaderboardTab">
        {this.renderMyRankCard(myScore)}

        <div className="Leaderboard">
          {userScores.length >= 3 && this.renderPodium(userScores.slice(0, 3))}
          
          {this.renderList(userScores)}

          {hasMore && (
            <div className="LoadMore">
              <Button
                className="Button Button--primary"
                loading={loading}
                onclick={onLoadMore}
              >
                {app.translator.trans('bigreja-bragalotto.lib.buttons.load_more')}
              </Button>
            </div>
          )}
        </div>
      </div>
    );
  }

  renderMyRankCard(myScore: any) {
    if (!myScore || !app.session.user) return null;

    const rank = myScore.attribute('rank');
    const points = myScore.totalPoints();
    const correct = myScore.correctPicks();
    const accuracy = myScore.accuracy();

    return (
      <div className="MyRankCard">
        <div className="MyRankCard-content">
          <div className="MyRankCard-user">
             <div className="avatar">
                {avatar(app.session.user, { className: 'Avatar' })}
             </div>
             <div className="info">
                <span className="label">{app.translator.trans('bigreja-bragalotto.forum.picks.your_pick')}</span>
                <span className="rank">#{rank}</span>
             </div>
          </div>
          <div className="MyRankCard-stats">
             <div className="stat">
               <span className="label">{app.translator.trans('bigreja-bragalotto.lib.common.points')}</span>
               <span className="value">{points}</span>
             </div>
             <div className="stat">
               <span className="label">{app.translator.trans('bigreja-bragalotto.lib.headers.correct')}</span>
               <span className="value">{correct}</span>
             </div>
             <div className="stat">
               <span className="label">{app.translator.trans('bigreja-bragalotto.lib.headers.accuracy')}</span>
               <span className="value">{accuracy}%</span>
             </div>
          </div>
        </div>
      </div>
    );
  }

  renderPodium(topThree: any[]) {
    const medals = ['🥇', '🥈', '🥉'];
    const positions = ['first', 'second', 'third'];

    return (
      <div className="Podium">
        {topThree.map((score: any, index: number) => {
          const user = score && (typeof score.user === 'function' ? score.user() : score.user);
          const totalPoints = typeof score.totalPoints === 'function' ? score.totalPoints() : score.totalPoints;
          const correctPicks = typeof score.correctPicks === 'function' ? score.correctPicks() : score.correctPicks;
          const isMe = app.session.user && user && app.session.user.id() === user.id();

          return (
            <div className={`Podium-card ${positions[index]} ${isMe ? 'is-me' : ''}`} key={index}>
              <div className="medal">{medals[index]}</div>
              <div className="rank">#{index + 1}</div>
              
              <div className="podium-avatar" style={{marginBottom: '5px'}}>
                 {user ? avatar(user, { className: 'Avatar' }) : null}
              </div>

              <div className="username">
                {user ? (typeof user.displayName === 'function' ? user.displayName() : user.displayName) : app.translator.trans('core.lib.username.deleted_text')}
              </div>
              <div className="points">
                {totalPoints}
                <small>{app.translator.trans('bigreja-bragalotto.lib.common.points')}</small>
              </div>
              <div className="stats">
                <div className="stat">
                  <div className="label">{app.translator.trans('bigreja-bragalotto.lib.headers.correct')}</div>
                  <div className="value">{correctPicks}</div>
                </div>
                <div className="stat">
                  <div className="label">{app.translator.trans('bigreja-bragalotto.lib.headers.accuracy')}</div>
                  <div className="value">{score.accuracy()}%</div>
                </div>
              </div>
            </div>
          );
        })}
      </div>
    );
  }

  renderList(userScores: any[]) {
    return (
      <div className="PickemList">
        <div className="PickemList-header">
          <div className="PickemList-cell type-rank">{app.translator.trans('bigreja-bragalotto.lib.headers.rank')}</div>
          <div className="PickemList-cell type-player">{app.translator.trans('bigreja-bragalotto.lib.headers.player')}</div>
          <div className="PickemList-cell type-stat">{app.translator.trans('bigreja-bragalotto.lib.common.points')}</div>
          <div className="PickemList-cell type-stat">{app.translator.trans('bigreja-bragalotto.lib.headers.correct')}</div>
          <div className="PickemList-cell type-stat">{app.translator.trans('bigreja-bragalotto.lib.headers.total')}</div>
          <div className="PickemList-cell type-stat">{app.translator.trans('bigreja-bragalotto.lib.headers.accuracy')}</div>
        </div>

        <div className="PickemList-body">
          {userScores.map((score: any, index: number) => {
            const user = score && (typeof score.user === 'function' ? score.user() : score.user);
            const scoreId = (score && (typeof score.id === 'function' ? score.id() : score.id)) || index;
            
            const totalPoints = typeof score.totalPoints === 'function' ? score.totalPoints() : score.totalPoints;
            const correctPicks = typeof score.correctPicks === 'function' ? score.correctPicks() : score.correctPicks;
            const totalPicks = typeof score.totalPicks === 'function' ? score.totalPicks() : score.totalPicks;
            const accuracy = typeof score.accuracy === 'function' ? score.accuracy() : 0;
            const isMe = app.session.user && user && app.session.user.id() === user.id();

            return (
              <div key={String(scoreId)} className={`PickemList-item ${index < 3 ? `top-${index + 1}` : ''} ${isMe ? 'is-me' : ''}`}>
                
                <div className="PickemList-cell type-rank">
                  <span className="mobile-label">{app.translator.trans('bigreja-bragalotto.lib.headers.rank')}</span>
                  <span className="value">#{index + 1}</span>
                </div>

                <div className="PickemList-cell type-player">
                  <div style={{display: 'flex', alignItems: 'center', gap: '10px'}}>
                      {user ? avatar(user, {className: 'Avatar--small'}) : null}
                      <span className="value">
                        {user ? (typeof user.displayName === 'function' ? user.displayName() : user.displayName) : app.translator.trans('core.lib.username.deleted_text')}
                      </span>
                  </div>
                </div>

                <div className="PickemList-cell type-stat">
                  <span className="mobile-label">{app.translator.trans('bigreja-bragalotto.lib.common.points')}</span>
                  <span className="value"><strong>{totalPoints}</strong></span>
                </div>

                <div className="PickemList-cell type-stat">
                  <span className="mobile-label">{app.translator.trans('bigreja-bragalotto.lib.headers.correct')}</span>
                  <span className="value">{correctPicks}</span>
                </div>

                <div className="PickemList-cell type-stat">
                  <span className="mobile-label">{app.translator.trans('bigreja-bragalotto.lib.headers.total')}</span>
                  <span className="value">{totalPicks}</span>
                </div>

                <div className="PickemList-cell type-stat">
                  <span className="mobile-label">{app.translator.trans('bigreja-bragalotto.lib.headers.accuracy')}</span>
                  <span className="value">{accuracy}%</span>
                </div>

              </div>
            );
          })}
        </div>
      </div>
    );
  }
}