/**
 * Global Search Component (⌘K / header)
 * Uses Platform Services search (Elasticsearch backed)
 */
import { useState } from 'react';
import { useGlobalSearch } from '@modules/platform-services/hooks/usePlatform';
import { Input } from '@shared/components/ui/input';
import { Dialog, DialogContent } from '@shared/components/ui/dialog';
import { Search, Users, BookOpen, DollarSign } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

export function GlobalSearch() {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');
  const { data, isLoading } = useGlobalSearch(query);
  const navigate = useNavigate();

  const results = data?.results || [];

  const handleSelect = (result: any) => {
    setOpen(false);
    setQuery('');
    if (result.type === 'student') navigate(`/academic/students?search=${result.id}`);
    else if (result.type === 'class') navigate(`/academic/classes`);
    // extend as needed
  };

  return (
    <>
      <div
        onClick={() => setOpen(true)}
        className="flex items-center gap-2 px-3 py-1.5 text-sm text-muted-foreground border rounded-md cursor-pointer hover:bg-accent w-72"
      >
        <Search className="h-4 w-4" />
        <span>Search students, classes, teachers... (⌘K)</span>
      </div>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="sm:max-w-[520px]">
          <div className="space-y-3">
            <Input
              autoFocus
              placeholder="Search everything..."
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              className="text-lg"
            />

            {query.length > 1 && (
              <div className="max-h-80 overflow-y-auto border rounded-md">
                {isLoading ? (
                  <div className="p-4 text-center text-sm">Searching...</div>
                ) : results.length === 0 ? (
                  <div className="p-4 text-sm text-muted-foreground">No results for "{query}"</div>
                ) : (
                  results.map((r: any, idx: number) => (
                    <div
                      key={idx}
                      onClick={() => handleSelect(r)}
                      className="flex items-center gap-3 p-3 hover:bg-accent cursor-pointer border-b last:border-none"
                    >
                      {r.type === 'student' && <Users className="h-4 w-4" />}
                      {r.type === 'class' && <BookOpen className="h-4 w-4" />}
                      {r.type === 'payment' && <DollarSign className="h-4 w-4" />}
                      <div>
                        <div className="font-medium text-sm">{r.title || r.name}</div>
                        <div className="text-xs text-muted-foreground">{r.subtitle || r.description}</div>
                      </div>
                    </div>
                  ))
                )}
              </div>
            )}
          </div>
        </DialogContent>
      </Dialog>
    </>
  );
}
