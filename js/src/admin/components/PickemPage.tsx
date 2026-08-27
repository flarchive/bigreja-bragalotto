import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Alert from 'flarum/common/components/Alert';
import TeamsTab from './TeamsTab';
import SeasonsTab from './SeasonsTab';
import CompetitionsTab from './CompetitionsTab';
import WeeksTab from './WeeksTab';
import EventsTab from './EventsTab';
import SettingsTab from './SettingsTab'; 

export default class PickemPage extends ExtensionPage {
  private activeTab: string = 'events';
  private loading: boolean = true;
  private error: string | null = null; 

  oninit(vnode: any) {
    super.oninit(vnode);
    
    const urlTab = m.route.param('tab');
    if (urlTab && ['events', 'teams', 'seasons', 'competitions', 'weeks', 'settings'].includes(urlTab)) {
      this.activeTab = urlTab;
    }
    
    this.loadData();
  }

  async loadData() {
    try {
      await Promise.all([
        app.store.find('bragalotto-teams'),
        app.store.find('bragalotto-seasons'),
        app.store.find('bragalotto-competitions'),
        app.store.find('bragalotto-weeks', { include: 'competition' }),
      ]);
      this.error = null;
    } catch (error: any) {
      console.error('Pickem admin data load error:', error);
      this.error = error.message || 'An unknown error occurred while loading data.';
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  content() {
    let tabContent;

    if (this.loading) {
      tabContent = (
        <div className="LoadingState">
          <LoadingIndicator />
        </div>
      );
    } else if (this.error) {
      tabContent = <Alert type="error">{this.error}</Alert>;
    } else {
      tabContent = this.renderTabContent();
    }

    return (
      <div className="PickemPage">
        <div className="container">
          <div className="PickemPage-tabs">
            {/* GÜNCELLENDİ: .events -> .matches */}
            {this.renderTab('events', 'fas fa-futbol', app.translator.trans('bigreja-bragalotto.lib.nav.matches'))}
            {this.renderTab('teams', 'fas fa-users', app.translator.trans('bigreja-bragalotto.lib.nav.teams'))}
            {this.renderTab('seasons', 'fas fa-calendar-alt', app.translator.trans('bigreja-bragalotto.lib.nav.seasons'))}
            {this.renderTab('competitions', 'fas fa-trophy', app.translator.trans('bigreja-bragalotto.lib.nav.competitions'))}
            {this.renderTab('weeks', 'fas fa-calendar-week', app.translator.trans('bigreja-bragalotto.lib.nav.weeks'))}
            {this.renderTab('settings', 'fas fa-cogs', app.translator.trans('bigreja-bragalotto.lib.nav.settings'))}
          </div>

          <div className="PickemPage-content">
            {tabContent}
          </div>
        </div>
      </div>
    );
  }

  renderTab(key: string, icon: string, label: string) {
    const isActive = this.activeTab === key;
    return (
      <button
        className={`Button ${isActive ? 'Button--primary' : ''}`}
        onclick={() => {
          this.activeTab = key;
          const currentRoute = m.route.get().split('?')[0];
          m.route.set(currentRoute, { tab: key }, { replace: true });
        }}
      >
        <i className={icon} /> <span>{label}</span>
      </button>
    );
  }

  renderTabContent() {
    switch (this.activeTab) {
      case 'events':
        return <EventsTab />;
      case 'teams':
        return <TeamsTab />;
      case 'seasons':
        return <SeasonsTab />;
      case 'competitions':
        return <CompetitionsTab />;
      case 'weeks':
        return <WeeksTab />;
      case 'settings':
        return <SettingsTab />;
      default:
        return <EventsTab />;
    }
  }
}