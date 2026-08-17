import Page from 'flarum/common/components/Page';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import extractText from 'flarum/common/utils/extractText';
import MatchesTab from './MatchesTab';
import MyPicksTab from './MyPicksTab';
import LeaderboardTab from './LeaderboardTab';
import IndexPage from 'flarum/forum/components/IndexPage';
import listItems from 'flarum/common/helpers/listItems';

export default class PickemPage extends Page {
  private activeTab: string = 'matches';
  private loading: boolean = true;
  
  private picks: Record<string, any> = {};
  private myPicksLoading: boolean = false;
  private myPicksHasMore: boolean = false;
  
  private userScores: any[] = [];
  private leaderboardLoading: boolean = false;
  private leaderboardHasMore: boolean = false;
  
  // YENİ: Kendi skorunu tutacak state
  private myScore: any = null;

  private filterDataLoaded: boolean = false;

  oninit(vnode: any) {
    super.oninit(vnode);

    if (!app.forum.attribute('bragalotto.canView')) {
      m.route.set('/'); 
      return;
    }

    this.activeTab = 'matches';
    this.loading = true;
    this.picks = {};
    this.myPicksLoading = false;
    this.myPicksHasMore = false;
    this.userScores = [];
    this.leaderboardLoading = false;
    this.leaderboardHasMore = false;
    this.myScore = null;
    this.filterDataLoaded = false; 

    this.loadInitialData();
  }

  oncreate(vnode: any) {
    super.oncreate(vnode);
    app.setTitle(extractText(app.translator.trans('bigreja-bragalotto.lib.nav.pickem')));
  }

  async loadInitialData() {
    try {
      const promises = [];

      if (app.session.user && app.forum.attribute('bragalotto.makePicks')) {
        promises.push(this.loadPicks(0));
        // YENİ: Eğer giriş yapmışsa kendi sıralamasını çek
        promises.push(this.loadMyRank());
      }
      
      promises.push(this.loadLeaderboard(0));
      promises.push(this.loadFilterData());

      await Promise.all(promises);

    } catch (error) {
      console.error('Error loading initial data:', error);
    } finally {
      this.loading = false;
      this.filterDataLoaded = true; 
      m.redraw();
    }
  }

  // YENİ: Kendi sıralamasını çeken fonksiyon
  async loadMyRank() {
    if (!app.session.user) return;
    
    try {
      // ListLeaderboardController'a eklediğimiz 'user' filtresini kullanıyoruz
      const results = (await app.store.find('bragalotto-user-scores', {
        filter: { user: app.session.user.id() },
        page: { limit: 1 }
      })) as any[];

      if (results && results.length > 0) {
        this.myScore = results[0];
      }
    } catch (error) {
      console.error('Error loading my rank:', error);
    }
  }

  async loadPicks(offset: number = 0) {
    if (!app.session.user || !app.forum.attribute('bragalotto.makePicks')) return;

    this.myPicksLoading = true;
    m.redraw();

    const limit = 20;

    try {
      const results = (await app.store.find('bragalotto-picks', {
        filter: { user: app.session.user.id() },
        include: 'event,event.homeTeam,event.awayTeam',
        page: { offset, limit }
      })) as any[];

      if (results && Array.isArray(results)) {
        const newPicksMap = results.reduce((acc: Record<string, any>, pick: any) => {
          try {
            const event = pick && (typeof pick.event === 'function' ? pick.event() : pick.event);
            if (event && typeof event.id === 'function') {
              acc[String(event.id())] = pick;
            }
          } catch (err) {
            console.warn('Invalid pick:', err);
          }
          return acc;
        }, {});

        this.picks = { ...this.picks, ...newPicksMap };
        this.myPicksHasMore = results.length >= limit;
      }
    } catch (error) {
      console.error('Error loading picks:', error);
    } finally {
      this.myPicksLoading = false;
      m.redraw();
    }
  }

  async loadLeaderboard(offset: number = 0) {
    this.leaderboardLoading = true;
    m.redraw();
    const limit = 20;
    try {
      const results = (await app.store.find('bragalotto-user-scores', { 
        include: 'user',
        page: { offset, limit } 
      })) as any[];
      
      if (offset === 0) {
        this.userScores = results;
      } else {
        this.userScores.push(...results);
      }
      this.leaderboardHasMore = results.length >= limit;
    } catch (error) {
      console.error('Error loading leaderboard:', error);
    } finally {
      this.leaderboardLoading = false;
      m.redraw();
    }
  }

  async loadFilterData() {
    try {
      await Promise.all([
        app.store.find('bragalotto-public-seasons'),
        app.store.find('bragalotto-public-teams'),
        app.store.find('bragalotto-public-weeks'),
        app.store.find('bragalotto-public-competitions'),
      ]);
    } catch (error) {
      console.error('Error loading filter data:', error);
    }
  }

  view() {
    return (
      <div className="IndexPage PickemPage"> 
        <header className="Hero PickemHero">
          <div className="container">
            <div className="containerNarrow">
              <h1 className="Hero-title">
                <i className="icon fas fa-trophy" />{' '}
                {app.translator.trans('bigreja-bragalotto.lib.nav.pickem')}
              </h1>
            </div>
          </div>
        </header>
        
        <div className="container">
          <div className="sideNavContainer">
            <nav className="IndexPage-nav sideNav">
              <ul>{listItems(IndexPage.prototype.sidebarItems().toArray())}</ul>
            </nav>
            <div className="IndexPage-results sideNavOffset">
              
              <div className="PickemPage-tabs">
                {this.renderTab('matches', app.translator.trans('bigreja-bragalotto.lib.nav.matches'))}
                
                {app.session.user && app.forum.attribute('bragalotto.makePicks') &&
                  this.renderTab('my_picks', app.translator.trans('bigreja-bragalotto.lib.nav.my_picks'))}

                {this.renderTab('leaderboard', app.translator.trans('bigreja-bragalotto.lib.nav.leaderboard'))}
              </div>

              {this.loading ? (
                <LoadingIndicator />
              ) : (
                <div className="PickemPage-tabContent">
                  {this.renderAllTabs()}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    );
  }

  renderTab(tabId: string, label: string) {
    const active = this.activeTab === tabId;
    return (
      <button
        className={`Button Button--flat PickemPage-tab ${active ? 'active' : ''}`}
        onclick={() => { this.activeTab = tabId; }}
      >
        {label}
      </button>
    );
  }

  renderAllTabs() {
    return (
      <>
        <div className={`PickemPage-tabPane ${this.activeTab === 'matches' ? 'active' : ''}`}>
          {this.filterDataLoaded && (
            <MatchesTab
              picks={this.picks}
              onPickChange={(picks: Record<string, any>) => { this.picks = picks; }}
            />
          )}
        </div>
        
        {app.session.user && app.forum.attribute('bragalotto.makePicks') && (
          <div className={`PickemPage-tabPane ${this.activeTab === 'my_picks' ? 'active' : ''}`}>
            <MyPicksTab 
              picks={this.picks} 
              loading={this.myPicksLoading}
              hasMore={this.myPicksHasMore}
              onLoadMore={() => this.loadPicks(Object.keys(this.picks).length)}
            />
          </div>
        )}
        
        <div className={`PickemPage-tabPane ${this.activeTab === 'leaderboard' ? 'active' : ''}`}>
          <LeaderboardTab 
            userScores={this.userScores}
            // YENİ: myScore'u gönderiyoruz
            myScore={this.myScore} 
            hasMore={this.leaderboardHasMore}
            loading={this.leaderboardLoading}
            onLoadMore={() => this.loadLeaderboard(this.userScores.length)}
          />
        </div>
      </>
    );
  }
}