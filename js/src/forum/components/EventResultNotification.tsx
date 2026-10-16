import Notification from 'flarum/forum/components/Notification';

export default class EventResultNotification extends Notification {
  icon() {
    return 'fas fa-trophy';
  }

  href() {
    return app.route('bragalotto');
  }

  content() {
    const notification = this.attrs.notification;
    const content = notification.content() || {};

    const homeTeam = content.homeTeam || app.translator.trans('bigreja-bragalotto.lib.common.home');
    const awayTeam = content.awayTeam || app.translator.trans('bigreja-bragalotto.lib.common.away');
    
    const homeScore = content.homeScore !== undefined ? content.homeScore : 0;
    const awayScore = content.awayScore !== undefined ? content.awayScore : 0;

    const reverse = app.forum.attribute<boolean>('bragalotto.reverseDisplay');

    if (reverse) {
        return app.translator.trans('bigreja-bragalotto.forum.notification', {
            home: awayTeam,
            hScore: awayScore,
            aScore: homeScore,
            away: homeTeam,
        });
    }

    return app.translator.trans('bigreja-bragalotto.forum.notification', {
      home: homeTeam,
      hScore: homeScore,
      aScore: awayScore,
      away: awayTeam,
    });
  }

  excerpt() {
    return null;
  }
}