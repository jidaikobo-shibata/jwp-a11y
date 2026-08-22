import fs from 'node:fs';
import { fixtureStateFile } from './paths';

export type FixtureState = {
  editorPostId: number;
  editorEditUrl: string;
  resultsPageId: number;
  resultsPageUrl: string;
  testedUrl: string;
};

export function readFixtureState(): FixtureState {
  return JSON.parse(fs.readFileSync(fixtureStateFile, 'utf8')) as FixtureState;
}
